<?php
declare(strict_types = 1);
namespace App\Tests\Unit\Extractor;

use App\Creator\RabbitMqCoreSplitMessage;
use App\Service\CoreSplitService;
use App\Service\RabbitSplitService;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;

class RabbitSplitServiceTest extends TestCase
{
    /**
     * @test
     */
    public function handleWorkerJobThrowsWithMissingJobUuid()
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $rabbitConnection = $this->prophesize(AMQPStreamConnection::class);
        $rabbitChannel = $this->prophesize(AMQPChannel::class);
        $rabbitConnection->channel()->willReturn($rabbitChannel->reveal());
        $coreSplitService = $this->prophesize(CoreSplitService::class);

        $subject = new RabbitSplitService(
            $loggerProphecy->reveal(),
            $rabbitConnection->reveal(),
            $coreSplitService->reveal(),
            'intercept-core-split-testing'
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
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $rabbitConnection = $this->prophesize(AMQPStreamConnection::class);
        $rabbitChannel = $this->prophesize(AMQPChannel::class);
        $rabbitConnection->channel()->willReturn($rabbitChannel->reveal());
        $coreSplitService = $this->prophesize(CoreSplitService::class);

        $subject = new RabbitSplitService(
            $loggerProphecy->reveal(),
            $rabbitConnection->reveal(),
            $coreSplitService->reveal(),
            'intercept-core-split-testing'
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
        ]);
        $message->getBody()->shouldBeCalled()->willReturn($messageBody);

        $coreSplitService->split(Argument::type(RabbitMqCoreSplitMessage::class))->shouldBeCalled();

        $subject->handleWorkerJob($message->reveal());
    }
}
