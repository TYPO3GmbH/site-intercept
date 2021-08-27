<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Unit\Service;

use App\Extractor\GithubPushEventForCore;
use App\Service\CoreSplitService;
use App\Service\CoreSplitServiceV8;
use App\Service\RabbitConsumerService;
use Doctrine\ORM\EntityManagerInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\IO\AbstractIO;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class RabbitConsumerServiceTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;
    /**
     * @test
     */
    public function handleWorkerJobThrowsWithMissingJobUuid()
    {
        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $rabbitConnection = $this->prophesize(AMQPStreamConnection::class);
        $rabbitChannel = $this->prophesize(AMQPChannel::class);
        $rabbitIo = $this->prophesize(AbstractIO::class);
        $rabbitConnection->channel()->willReturn($rabbitChannel->reveal());
        $rabbitConnection->getIO()->willReturn($rabbitIo->reveal());
        $coreSplitService = $this->prophesize(CoreSplitService::class);
        $coreSplitServiceV8 = $this->prophesize(CoreSplitServiceV8::class);

        $subject = new RabbitConsumerService(
            $entityManager->reveal(),
            $rabbitConnection->reveal(),
            $coreSplitService->reveal(),
            $coreSplitServiceV8->reveal(),
            'intercept-core-split-testing',
            'foo'
        );

        $message = $this->prophesize(AMQPMessage::class);
        $messageBody = json_encode([]);
        $message->getBody()->shouldBeCalled()->willReturn($messageBody);

        $this->expectException(\RuntimeException::class);
        $subject->handleWorkerJob($message->reveal());
    }

    /**
     * @test
     */
    public function handleWorkerJobCallsSplitService()
    {
        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $rabbitConnection = $this->prophesize(AMQPStreamConnection::class);
        $rabbitChannel = $this->prophesize(AMQPChannel::class);
        $rabbitIo = $this->prophesize(AbstractIO::class);
        $rabbitConnection->channel()->willReturn($rabbitChannel->reveal());
        $rabbitConnection->getIO()->willReturn($rabbitIo->reveal());
        $coreSplitService = $this->prophesize(CoreSplitService::class);
        $coreSplitServiceV8 = $this->prophesize(CoreSplitServiceV8::class);

        $subject = new RabbitConsumerService(
            $entityManager->reveal(),
            $rabbitConnection->reveal(),
            $coreSplitService->reveal(),
            $coreSplitServiceV8->reveal(),
            'intercept-core-split-testing',
            'foo'
        );

        $message = $this->prophesize(AMQPMessage::class);
        $message->delivery_info = [
            'channel' => $rabbitChannel->reveal(),
            'delivery_tag' => 'delivery-tag',
        ];
        $messageBody = json_encode([
            'sourceBranch' => 'source-branch',
            'targetBranch' => 'target-branch',
            'jobUuid' => 'job-uuid',
            'type' => 'patch',
        ]);
        $message->getBody()->shouldBeCalled()->willReturn($messageBody);

        $coreSplitService->split(Argument::type(GithubPushEventForCore::class), Argument::type(AbstractIO::class))->shouldBeCalled();

        $subject->handleWorkerJob($message->reveal());
    }

    /**
     * @test
     */
    public function handleWorkerJobCallsTagService()
    {
        $entityManager = $this->prophesize(EntityManagerInterface::class);
        $rabbitConnection = $this->prophesize(AMQPStreamConnection::class);
        $rabbitChannel = $this->prophesize(AMQPChannel::class);
        $rabbitIo = $this->prophesize(AbstractIO::class);
        $rabbitConnection->channel()->willReturn($rabbitChannel->reveal());
        $rabbitConnection->getIO()->willReturn($rabbitIo->reveal());
        $coreSplitService = $this->prophesize(CoreSplitService::class);
        $coreSplitServiceV8 = $this->prophesize(CoreSplitServiceV8::class);

        $subject = new RabbitConsumerService(
            $entityManager->reveal(),
            $rabbitConnection->reveal(),
            $coreSplitService->reveal(),
            $coreSplitServiceV8->reveal(),
            'intercept-core-split-testing',
            'foo'
        );

        $message = $this->prophesize(AMQPMessage::class);
        $message->delivery_info = [
            'channel' => $rabbitChannel->reveal(),
            'delivery_tag' => 'delivery-tag',
        ];
        $messageBody = json_encode([
            'sourceBranch' => 'source-branch',
            'targetBranch' => 'target-branch',
            'jobUuid' => 'job-uuid',
            'type' => 'tag',
            'tag' => 'v9.5.1',
        ], JSON_THROW_ON_ERROR, 512);
        $message->getBody()->shouldBeCalled()->willReturn($messageBody);

        $coreSplitService->tag(Argument::type(GithubPushEventForCore::class), Argument::type(AbstractIO::class))->shouldBeCalled();

        $subject->handleWorkerJob($message->reveal());
    }
}
