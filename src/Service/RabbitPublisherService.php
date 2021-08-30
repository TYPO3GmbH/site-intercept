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
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\PropertyNormalizer;
use Symfony\Component\Serializer\Serializer;

class RabbitPublisherService
{
    /**
     * @var AMQPStreamConnection The rabbit connection
     */
    private AMQPStreamConnection $rabbitConnection;

    /**
     * @var string Name of the queue to push to and fetch from
     */
    private string $queueName;

    private EntityManagerInterface $entityManager;

    /**
     * RabbitPublisherService constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param AMQPStreamConnection $rabbitConnection
     * @param string $rabbitSplitQueue
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        AMQPStreamConnection $rabbitConnection,
        string $rabbitSplitQueue
    ) {
        $this->queueName = $rabbitSplitQueue;
        $this->rabbitConnection = $rabbitConnection;
        $this->entityManager = $entityManager;
    }

    /**
     * Push a core split job message to rabbit queue
     *
     * @param GithubPushEventForCore $message
     * @param string $trigger 'api' or 'interface'
     */
    public function pushNewCoreSplitJob(GithubPushEventForCore $message, string $trigger): void
    {
        $jsonMessage = (new Serializer([new PropertyNormalizer()], [new JsonEncoder()]))
            ->serialize($message, 'json');
        $rabbitMessage = new AMQPMessage($jsonMessage, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
        $rabbitChannel = $this->rabbitConnection->channel();
        $rabbitChannel->queue_declare($this->queueName, false, true, false, false);
        $rabbitChannel->basic_publish($rabbitMessage, '', $this->queueName);
        $type = $message->type === 'patch' ? HistoryEntryType::PATCH : HistoryEntryType::TAG;
        $this->entityManager->persist(
            (new HistoryEntry())
                ->setType($type)
                ->setStatus(SplitterStatus::QUEUED)
                ->setData(
                    [
                        'type' => $type,
                        'status' => SplitterStatus::QUEUED,
                        'triggeredBy' => $trigger === HistoryEntryTrigger::API ? HistoryEntryTrigger::API : HistoryEntryTrigger::WEB,
                        'message' => 'Queued a core split job to queue ' . $this->queueName . ' with message ' . $jsonMessage,
                        'job_uuid' => $message->jobUuid,
                        'repository' => $message->repositoryFullName,
                        'sourceBranch' => $message->sourceBranch,
                        'targetBranch' => $message->targetBranch,
                        'tag' => $message->tag,
                    ]
                )
        );
        $this->entityManager->flush();
    }
}
