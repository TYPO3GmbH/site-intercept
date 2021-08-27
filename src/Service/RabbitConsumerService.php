<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Entity\HistoryEntry;
use App\Enum\HistoryEntryTrigger;
use App\Enum\HistoryEntryType;
use App\Enum\SplitterStatus;
use App\Extractor\GithubPushEventForCore;
use Doctrine\ORM\EntityManagerInterface;
use ErrorException;
use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\IO\AbstractIO;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

class RabbitConsumerService
{
    /**
     * @var AMQPChannel The rabbit channel to messages to and fetch from
     */
    private AMQPChannel $rabbitChannel;

    /**
     * @var AbstractIO Direct rabbit IO connection, used to send heartbeats in between single worker jobs
     */
    private AbstractIO $rabbitIO;

    /**
     * @var string Name of the queue to push to and fetch from
     */
    private string $queueName;

    private CoreSplitServiceInterface $coreSplitService;

    private CoreSplitServiceInterface $coreSplitServiceV8;

    /**
     * @var string the v8 ELTS repository name
     */
    private string $eltsRepositoryNameV8;
    private EntityManagerInterface $entityManager;

    /**
     * RabbitPublisherService constructor.
     *
     * @param AMQPStreamConnection $rabbitConnection
     * @param CoreSplitService $coreSplitService
     * @param CoreSplitServiceV8 $coreSplitServiceV8
     * @param string $rabbitSplitQueue
     * @param string $eltsRepositoryNameV8
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        AMQPStreamConnection $rabbitConnection,
        CoreSplitServiceInterface $coreSplitService,
        CoreSplitServiceInterface $coreSplitServiceV8,
        string $rabbitSplitQueue,
        string $eltsRepositoryNameV8
    ) {
        $this->queueName = $rabbitSplitQueue;
        $this->eltsRepositoryNameV8 = $eltsRepositoryNameV8;
        $this->coreSplitService = $coreSplitService;
        $this->coreSplitServiceV8 = $coreSplitServiceV8;
        $this->rabbitChannel = $rabbitConnection->channel();
        $this->rabbitChannel->queue_declare($this->queueName, false, true, false, false);
        // Note getIO() is 'internal' / 'deprecated' in amqplib 2.9.2. However, there is
        // currently no other way to send heardbeats in between jobs manually, which also
        // updates the 'last_read' / 'last_write' values.
        // Solution from https://blog.mollie.com/keeping-rabbitmq-connections-alive-in-php-b11cb657d5fb
        // Default heartbeat: 60 seconds, so any single job running longer than 2 minutes (two heartbeats missed), will crash.
        // Thus, the IO object is given down to jobs, to send a heartbeat in between single units of jobs
        $this->rabbitIO = $rabbitConnection->getIO();
        $this->entityManager = $entityManager;
    }

    /**
     * Entry point with endless loop for git split worker, used by worker command.
     *
     * @throws ErrorException
     * @codeCoverageIgnore Not easy to test this endless loop in a good way
     */
    public function workerLoop(): void
    {
        $this->rabbitChannel->basic_consume($this->queueName, '', false, false, false, false, function (AMQPMessage $message) : void {
            $this->handleWorkerJob($message);
        });
        while (count($this->rabbitChannel->callbacks)) {
            $this->rabbitChannel->wait();
        }
    }

    /**
     * Handle a single split job
     *
     * @param AMQPMessage $message
     * @throws Exception
     */
    public function handleWorkerJob(AMQPMessage $message): void
    {
        /** @var GithubPushEventForCore $event */
        $event = (new Serializer([new PropertyNormalizer()], [new JsonEncoder()]))
            ->deserialize($message->getBody(), GithubPushEventForCore::class, 'json');
        if (empty($event->jobUuid)) {
            throw new RuntimeException('Required job uuid missing');
        }
        $type = $event->type === 'patch' ? HistoryEntryType::PATCH : HistoryEntryType::TAG;
        $this->entityManager->persist(
            (new HistoryEntry())
                ->setType($type)
                ->setStatus(SplitterStatus::DISPATCH)
                ->setGroup($event->jobUuid)
                ->setData(
                    [
                        'type' => $type,
                        'status' => SplitterStatus::DISPATCH,
                        'triggeredBy' => HistoryEntryTrigger::CLI,
                        'message' => 'Handling a git split worker job',
                        'job_uuid' => $event->jobUuid,
                        'sourceBranch' => $event->sourceBranch,
                        'targetBranch' => $event->targetBranch,
                        'tag' => $event->tag,
                    ]
                )
        );
        $this->entityManager->flush();
        $splitter = $this->getCoreSplitter($event);
        if ($event->type === 'patch') {
            $splitter->split($event, $this->rabbitIO);
        } elseif ($event->type === 'tag') {
            $splitter->tag($event, $this->rabbitIO);
        }
        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
        $this->entityManager->persist(
            (new HistoryEntry())
                ->setType($type)
                ->setStatus(SplitterStatus::DONE)
                ->setGroup($event->jobUuid)
                ->setData(
                    [
                        'type' => $type,
                        'status' => SplitterStatus::DONE,
                        'triggeredBy' => HistoryEntryTrigger::CLI,
                        'message' => 'Handling a git split worker job',
                        'tag' => $event->tag,
                        'job_uuid' => $event->jobUuid,
                        'splitter' => get_class($splitter),
                        'repository' => $event->repositoryFullName,
                        'sourceBranch' => $event->sourceBranch,
                        'targetBranch' => $event->targetBranch,
                    ]
                )
        );
        $this->entityManager->flush();
    }

    private function getCoreSplitter(GithubPushEventForCore $event): CoreSplitServiceInterface
    {
        if ($event->repositoryFullName === $this->eltsRepositoryNameV8) {
            return $this->coreSplitServiceV8;
        }
        return $this->coreSplitService;
    }
}
