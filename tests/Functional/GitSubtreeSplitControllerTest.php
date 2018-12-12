<?php
declare(strict_types = 1);
namespace App\Tests\Functional;

use App\Bundle\TestDoubleBundle;
use App\Service\CoreSplitService;
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
        // Mock core split service that is a DI dependency of RabbitPublisherService
        $coreSplitServiceProphecy = $this->prophesize(CoreSplitService::class);
        TestDoubleBundle::addProphecy(CoreSplitService::class, $coreSplitServiceProphecy);

        $rabbitConnectionProphecy = $this->prophesize(AMQPStreamConnection::class);
        TestDoubleBundle::addProphecy(AMQPStreamConnection::class, $rabbitConnectionProphecy);

        $rabbitChannel = $this->prophesize(AMQPChannel::class);
        $rabbitConnectionProphecy->channel()->shouldBeCalled()->willReturn($rabbitChannel->reveal());

        $rabbitChannel->queue_declare('intercept-core-split-testing', false, true, false, false)->shouldBeCalled();
        $rabbitChannel->basic_publish(Argument::type(AMQPMessage::class), '', 'intercept-core-split-testing')->shouldBeCalled();

        $kernel = new \App\Kernel('test', true);
        $kernel->boot();
        $request = require __DIR__ . '/Fixtures/GitSubtreeSplitPatchRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }

    /**
     * @test
     */
    public function splitIsQueuedForTagJob()
    {
        // Mock core split service that is a DI dependency of RabbitPublisherService
        $coreSplitServiceProphecy = $this->prophesize(CoreSplitService::class);
        TestDoubleBundle::addProphecy(CoreSplitService::class, $coreSplitServiceProphecy);

        $rabbitConnectionProphecy = $this->prophesize(AMQPStreamConnection::class);
        TestDoubleBundle::addProphecy(AMQPStreamConnection::class, $rabbitConnectionProphecy);

        $rabbitChannel = $this->prophesize(AMQPChannel::class);
        $rabbitConnectionProphecy->channel()->shouldBeCalled()->willReturn($rabbitChannel->reveal());

        $rabbitChannel->queue_declare('intercept-core-split-testing', false, true, false, false)->shouldBeCalled();
        $rabbitChannel->basic_publish(Argument::type(AMQPMessage::class), '', 'intercept-core-split-testing')->shouldBeCalled();

        $kernel = new \App\Kernel('test', true);
        $kernel->boot();
        $request = require __DIR__ . '/Fixtures/GitSubtreeSplitTagRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }

    /**
     * @test
     */
    public function nothingDoneWithBadRequest()
    {
        // Mock core split service that is a DI dependency of RabbitPublisherService
        $coreSplitServiceProphecy = $this->prophesize(CoreSplitService::class);
        TestDoubleBundle::addProphecy(CoreSplitService::class, $coreSplitServiceProphecy);

        $rabbitConnectionProphecy = $this->prophesize(AMQPStreamConnection::class);
        TestDoubleBundle::addProphecy(AMQPStreamConnection::class, $rabbitConnectionProphecy);

        $rabbitConnectionProphecy->channel()->shouldNotBeCalled();

        $kernel = new \App\Kernel('test', true);
        $kernel->boot();
        $request = require __DIR__ . '/Fixtures/GitSubtreeSplitBadRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }
}
