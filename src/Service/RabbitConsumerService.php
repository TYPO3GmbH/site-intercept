<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Extractor\GithubPushEventForCore;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class RabbitConsumerService
{
    /**
     * @var AMQPChannel The rabbit channel to messages to and fetch from
     */
    private $rabbitChannel;

    /**
     * @var string Name of the queue to push to and fetch from
     */
    private $queueName;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CoreSplitService Service executing the split job
     */
    private $coreSplitService;

    /**
     * RabbitPublisherService constructor.
     *
     * @param LoggerInterface $logger
     * @param AMQPStreamConnection $rabbitConnection
     * @param CoreSplitService $coreSplitService
     * @param SerializerInterface $serializer
     * @param string $rabbitSplitQueue
     */
    public function __construct(
        LoggerInterface $logger,
        AMQPStreamConnection $rabbitConnection,
        CoreSplitService $coreSplitService,
        string $rabbitSplitQueue
    ) {
        $this->logger = $logger;
        $this->queueName = $rabbitSplitQueue;
        $this->coreSplitService = $coreSplitService;
        $this->rabbitChannel = $rabbitConnection->channel();
        $this->rabbitChannel->queue_declare($this->queueName, false, true, false, false);
    }

    /**
     * Entry point with endless loop for git split worker, used by worker command.
     *
     * @throws \ErrorException
     * @codeCoverageIgnore Not easy to test this endless loop in a good way
     */
    public function workerLoop(): void
    {
        $this->rabbitChannel->basic_consume($this->queueName, '', false, false, false, false, [$this, 'handleWorkerJob']);
        while (count($this->rabbitChannel->callbacks)) {
            $this->rabbitChannel->wait();
        }
    }

    /**
     * Handle a single split job
     *
     * @param AMQPMessage $message
     * @throws \Exception
     */
    public function handleWorkerJob(AMQPMessage $message): void
    {
        $serializer = new Serializer([new PropertyNormalizer()], [new JsonEncoder()]);
        /** @var GithubPushEventForCore $event */
        $event = $serializer->deserialize($message->getBody(), GithubPushEventForCore::class, 'json');
        if (empty($event->jobUuid)) {
            throw new \RuntimeException('Required job uuid missing');
        }
        $this->logger->info('handling a git split worker job', ['job_uuid' => $event->jobUuid, 'type' => $event->type]);
        if ($event->type === 'patch') {
            $this->coreSplitService->split($event);
        } elseif ($event->type === 'tag') {
            $this->coreSplitService->tag($event);
        }
        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
        $this->logger->info('finished a git split worker job', ['job_uuid' => $event->jobUuid, 'type' => $event->type]);
    }
}
