<?php
declare(strict_types = 1);
namespace App\Tests\Functional;

use App\Service\CoreSplitService;
use App\Service\RabbitPublisherService;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class GitSubtreeSplitControllerTest extends TestCase
{
    /**
     * @test
     */
    public function splitIsQueued()
    {
        $kernel = new \App\Kernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer();

        // Mock core split service that is a DI dependency of RabbitPublisherService
        $coreSplitService = $this->prophesize(CoreSplitService::class);
        $container->set(CoreSplitService::class, $coreSplitService->reveal());

        $rabbitConnection = $this->prophesize(AMQPStreamConnection::class);
        $container->set(AMQPStreamConnection::class, $rabbitConnection->reveal());

        $rabbitChannel = $this->prophesize(AMQPChannel::class);
        $rabbitConnection->channel()->shouldBeCalled()->willReturn($rabbitChannel->reveal());

        $rabbitChannel->queue_declare('intercept-core-split-testing', false, true, false, false)->shouldBeCalled();
        $rabbitChannel->basic_publish(Argument::type(AMQPMessage::class), '', 'intercept-core-split-testing')->shouldBeCalled();

        $request = require __DIR__ . '/Fixtures/GitSubtreeSplitPatchRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }

    /**
     * @test
     */
    public function splitIsQueuedForTagJob()
    {
        $kernel = new \App\Kernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer();

        // Mock core split service that is a DI dependency of RabbitPublisherService
        $coreSplitService = $this->prophesize(CoreSplitService::class);
        $container->set(CoreSplitService::class, $coreSplitService->reveal());

        $rabbitConnection = $this->prophesize(AMQPStreamConnection::class);
        $container->set(AMQPStreamConnection::class, $rabbitConnection->reveal());

        $rabbitChannel = $this->prophesize(AMQPChannel::class);
        $rabbitConnection->channel()->shouldBeCalled()->willReturn($rabbitChannel->reveal());

        $rabbitChannel->queue_declare('intercept-core-split-testing', false, true, false, false)->shouldBeCalled();
        $rabbitChannel->basic_publish(Argument::type(AMQPMessage::class), '', 'intercept-core-split-testing')->shouldBeCalled();

        $request = require __DIR__ . '/Fixtures/GitSubtreeSplitTagRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }

    /**
     * @test
     */
    public function nothingDoneWithBadRequest()
    {
        $kernel = new \App\Kernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer();

        // Mock core split service that is a DI dependency of RabbitPublisherService
        $coreSplitService = $this->prophesize(CoreSplitService::class);
        $container->set(CoreSplitService::class, $coreSplitService->reveal());

        $rabbitConnection = $this->prophesize(AMQPStreamConnection::class);
        $container->set(AMQPStreamConnection::class, $rabbitConnection->reveal());

        $rabbitChannel = $this->prophesize(AMQPChannel::class);
        $rabbitConnection->channel()->shouldBeCalled()->willReturn($rabbitChannel->reveal());

        $rabbitChannel->queue_declare('intercept-core-split-testing', false, true, false, false)->shouldBeCalled();
        $rabbitChannel->basic_publish(Argument::cetera())->shouldNotBeCalled();

        $request = require __DIR__ . '/Fixtures/GitSubtreeSplitBadRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }
}
