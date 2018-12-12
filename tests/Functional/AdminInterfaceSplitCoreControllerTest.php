<?php
declare(strict_types = 1);
namespace App\Tests\Functional;

use App\Bundle\TestDoubleBundle;
use App\Client\RabbitManagementClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminInterfaceSplitCoreControllerTest extends WebTestCase
{
    /**
     * @test
     */
    public function rabbitDataIsRendered()
    {
        TestDoubleBundle::addProphecy(AMQPStreamConnection::class, $this->prophesize(AMQPStreamConnection::class));

        $rabbitClientProphecy = $this->prophesize(RabbitManagementClient::class);
        TestDoubleBundle::addProphecy(RabbitManagementClient::class, $rabbitClientProphecy);
        $rabbitClientProphecy->get(Argument::cetera())->shouldBeCalled()->willReturn(
            new Response(200, [], json_encode(['consumers' => 1, 'messages' => 2]))
        );

        $client = static::createClient();
        $client->request('GET', '/admin/split/core');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertRegExp('/Rabbit: online/', $client->getResponse()->getContent());
        $this->assertRegExp('/Worker: online/', $client->getResponse()->getContent());
        $this->assertRegExp('/Jobs: 2/', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function rabbitDownIsRendered()
    {
        TestDoubleBundle::addProphecy(AMQPStreamConnection::class, $this->prophesize(AMQPStreamConnection::class));

        $rabbitClientProphecy = $this->prophesize(RabbitManagementClient::class);
        TestDoubleBundle::addProphecy(RabbitManagementClient::class, $rabbitClientProphecy);
        $rabbitClientProphecy->get(Argument::cetera())->shouldBeCalled()->willThrow(
            new ClientException('testing', new Request('GET', ''))
        );

        $client = static::createClient();
        $client->request('GET', '/admin/split/core');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertRegExp('/Rabbit: offline/', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function splitCoreCanBeTriggered()
    {
        $rabbitClientProphecy = $this->prophesize(RabbitManagementClient::class);
        TestDoubleBundle::addProphecy(RabbitManagementClient::class, $rabbitClientProphecy);
        $rabbitClientProphecy->get(Argument::cetera())->willReturn(
            new Response(200, [], json_encode(['consumers' => 1, 'messages' => 2]))
        );
        $rabbitClientProphecy = $this->prophesize(RabbitManagementClient::class);
        TestDoubleBundle::addProphecy(RabbitManagementClient::class, $rabbitClientProphecy);
        $rabbitClientProphecy->get(Argument::cetera())->willReturn(
            new Response(200, [], json_encode(['consumers' => 1, 'messages' => 3]))
        );

        TestDoubleBundle::addProphecy(AMQPStreamConnection::class, $this->prophesize(AMQPStreamConnection::class));

        // rabbit stream client double for the second request
        $rabbitStreamProphecy = $this->prophesize(AMQPStreamConnection::class);
        TestDoubleBundle::addProphecy(AMQPStreamConnection::class, $rabbitStreamProphecy);
        $rabbitChannelProphecy = $this->prophesize(AMQPChannel::class);
        $rabbitStreamProphecy->channel()->shouldBeCalled()->willReturn($rabbitChannelProphecy->reveal());
        $rabbitChannelProphecy->queue_declare(Argument::cetera())->shouldBeCalled();
        $rabbitChannelProphecy->basic_publish(Argument::cetera())->shouldBeCalled();

        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/split/core');

        // Get the rendered form, feed it with some data and submit it
        $form = $crawler->selectButton('Trigger master')->form();
        $client->submit($form);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // The branch is shown
        $this->assertRegExp(
            '/Triggered split job for core branch &quot;master&quot;/',
            $client->getResponse()->getContent()
        );
    }

    /**
     * @test
     */
    public function tagCoreCanBeTriggered()
    {
        $rabbitClientProphecy = $this->prophesize(RabbitManagementClient::class);
        TestDoubleBundle::addProphecy(RabbitManagementClient::class, $rabbitClientProphecy);
        $rabbitClientProphecy->get(Argument::cetera())->willReturn(
            new Response(200, [], json_encode(['consumers' => 1, 'messages' => 2]))
        );
        $rabbitClientProphecy = $this->prophesize(RabbitManagementClient::class);
        TestDoubleBundle::addProphecy(RabbitManagementClient::class, $rabbitClientProphecy);
        $rabbitClientProphecy->get(Argument::cetera())->willReturn(
            new Response(200, [], json_encode(['consumers' => 1, 'messages' => 3]))
        );

        TestDoubleBundle::addProphecy(AMQPStreamConnection::class, $this->prophesize(AMQPStreamConnection::class));

        // rabbit stream client double for the second request
        $rabbitStreamProphecy = $this->prophesize(AMQPStreamConnection::class);
        TestDoubleBundle::addProphecy(AMQPStreamConnection::class, $rabbitStreamProphecy);
        $rabbitChannelProphecy = $this->prophesize(AMQPChannel::class);
        $rabbitStreamProphecy->channel()->shouldBeCalled()->willReturn($rabbitChannelProphecy->reveal());
        $rabbitChannelProphecy->queue_declare(Argument::cetera())->shouldBeCalled();
        $rabbitChannelProphecy->basic_publish(Argument::cetera())->shouldBeCalled();

        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/split/core');

        // Get the rendered form, feed it with some data and submit it
        $form = $crawler->selectButton('Trigger sub repo tagging')->form();
        $form['split_core_tag_form[tag]'] = 'v9.5.1';
        $client->submit($form);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // The tag is shown
        $this->assertRegExp(
            '/Triggered tag job with tag &quot;v9.5.1&quot;/',
            $client->getResponse()->getContent()
        );
    }
}
