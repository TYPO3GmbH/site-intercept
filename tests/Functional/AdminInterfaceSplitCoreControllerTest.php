<?php
declare(strict_types = 1);
namespace App\Tests\Functional;

use App\Bundle\TestDoubleBundle;
use App\Client\RabbitManagementClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminInterfaceSplitCoreControllerTest extends WebTestCase
{
    /**
     * @test
     */
    public function rabbitDataIsRendered()
    {
        $rabbitClientProphecy = $this->prophesize(RabbitManagementClient::class);
        TestDoubleBundle::addProphecy('App\Client\RabbitManagementClient', $rabbitClientProphecy);
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
        $rabbitClientProphecy = $this->prophesize(RabbitManagementClient::class);
        TestDoubleBundle::addProphecy('App\Client\RabbitManagementClient', $rabbitClientProphecy);
        $rabbitClientProphecy->get(Argument::cetera())->shouldBeCalled()->willThrow(
            new ClientException('testing', new Request('GET', ''))
        );

        $client = static::createClient();
        $client->request('GET', '/admin/split/core');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertRegExp('/Rabbit: offline/', $client->getResponse()->getContent());
    }
}
