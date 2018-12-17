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
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

class RabbitPublisherService
{
    /**
     * @var AMQPStreamConnection The rabbit connection
     */
    private $rabbitConnection;

    /**
     * @var string Name of the queue to push to and fetch from
     */
    private $queueName;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * RabbitPublisherService constructor.
     *
     * @param LoggerInterface $logger
     * @param AMQPStreamConnection $rabbitConnection
     * @param string $rabbitSplitQueue
     */
    public function __construct(LoggerInterface $logger, AMQPStreamConnection $rabbitConnection, string $rabbitSplitQueue)
    {
        $this->logger = $logger;
        $this->queueName = $rabbitSplitQueue;
        $this->rabbitConnection = $rabbitConnection;
    }

    /**
     * Push a core split job message to rabbit queue
     *
     * @param GithubPushEventForCore $message
     * @param string $trigger 'api' or 'interface'
     */
    public function pushNewCoreSplitJob(GithubPushEventForCore $message, string $trigger): void
    {
        $serializer = new Serializer([new PropertyNormalizer()], [new JsonEncoder()]);
        $jsonMessage = $serializer->serialize($message, 'json');
        $rabbitMessage = new AMQPMessage($jsonMessage, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
        $rabbitChannel = $this->rabbitConnection->channel();
        $rabbitChannel->queue_declare($this->queueName, false, true, false, false);
        $rabbitChannel->basic_publish($rabbitMessage, '', $this->queueName);
        $this->logger->info(
            'Queued a core split job to queue ' . $this->queueName . ' with message ' . $jsonMessage,
            [
                'job_uuid' => $message->jobUuid,
                'type' => $message->type,
                'triggeredBy' => $trigger,
                'sourceBranch' => $message->sourceBranch,
                'targetBranch' => $message->targetBranch,
                'tag' => $message->tag,
                'status' => 'queued',
            ]
        );
    }
}
