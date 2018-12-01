<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;


use App\Creator\RabbitMqCoreSplitMessage;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;

class RabbitSplitService
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
     * RabbitSplitService constructor.
     *
     * @param LoggerInterface $logger
     * @param AMQPStreamConnection $rabbitConnection
     * @param string $rabbitSplitQueue
     */
    public function __construct(LoggerInterface $logger, AMQPStreamConnection $rabbitConnection, string $rabbitSplitQueue)
    {
        $this->logger = $logger;
        $this->queueName = $rabbitSplitQueue;
        $this->rabbitChannel = $rabbitConnection->channel();
        $this->rabbitChannel->queue_declare($this->queueName, false, true, false, false);
    }

    /**
     * Push a core split job message to rabbit queue
     *
     * @param RabbitMqCoreSplitMessage $rabbitMessage
     */
    public function pushNewCoreSplitJob(RabbitMqCoreSplitMessage $rabbitMessage): void
    {
        $jsonMessage = json_encode($rabbitMessage);
        $message = new AMQPMessage($jsonMessage, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
        $this->rabbitChannel->basic_publish($message, '', $this->queueName);
        $this->logger->info('Queued a core split job to queue ' . $this->queueName . ' with message ' . $jsonMessage);
    }

    /**
     * Entry point with endless loop for git split worker, used by worker command
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
     */
    public function handleWorkerJob(AMQPMessage $message): void
    {
        $this->logger->info('handling a git split worker job');
        $jobData = json_decode($message->getBody(), true);
        $rabbitMessage = new RabbitMqCoreSplitMessage($jobData['sourceBranch'], $jobData['targetBranch']);
        $this->coreSplit($rabbitMessage);
        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
        $this->logger->info('finished a git split worker job');
    }

    /**
     * Execute core splitting
     *
     * @param RabbitMqCoreSplitMessage $rabbitMessage
     */
    private function coreSplit(RabbitMqCoreSplitMessage $rabbitMessage): void
    {
        $sourceBranch = $rabbitMessage->sourceBranch;
        $targetBranch = $rabbitMessage->targetBranch;
        $execOutput = [];
        $execReturn = 0;
        exec(
            __DIR__ . '/../../bin/split.sh ' . escapeshellarg($sourceBranch) . ' ' . escapeshellarg($targetBranch) . ' 2>&1',
            $execOutput,
            $execReturn
        );

        $this->logger->info(
        'github git split'
        . ' from ' . $sourceBranch
        . ' to ' . $targetBranch
        . ' script return ' . $execReturn
        . ' with script payload:'
        );
        $this->logger->info(print_r($execOutput, true));
    }
}