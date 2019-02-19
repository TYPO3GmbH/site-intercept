<?php
declare(strict_types = 1);
namespace App\Tests\Functional;

use App\Bundle\TestDoubleBundle;
use App\Client\BambooClient;
use App\Client\GerritClient;
use App\Client\GraylogClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminInterfaceDocsControllerTest extends WebTestCase
{
    /**
     * @test
     */
    public function recentLogMessagesAreRendered()
    {
        $graylogClientProphecy = $this->prophesize(GraylogClient::class);
        TestDoubleBundle::addProphecy(GraylogClient::class, $graylogClientProphecy);
        $graylogClientProphecy->get(Argument::cetera())->shouldBeCalled()->willReturn(
            new Response(200, [], json_encode([
                'messages' => [
                    0 => [
                        'message' => [
                            'application' => 'intercept',
                            'ctxt_type' => 'triggerBambooDocsFluidVh',
                            'env' => 'prod',
                            'level' => 6,
                            'message' => 'my message',
                            'timestamp' => '2018-12-16T22:07:04.815Z',
                            'ctxt_bambooKey' => 'CORE-DRF-1234',
                            'ctxt_triggeredBy' => 'web',
                        ]
                    ]
                ]
            ]))
        );

        $client = static::createClient();
        $client->request('GET', '/admin/docs');
        $this->assertRegExp('/12345/', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function renderingWorksIfGraylogThrows()
    {
        $graylogClientProphecy = $this->prophesize(GraylogClient::class);
        TestDoubleBundle::addProphecy(GraylogClient::class, $graylogClientProphecy);
        $graylogClientProphecy->get(Argument::cetera())->shouldBeCalled()->willThrow(
            new ClientException('testing', new Request('GET', ''))
        );

        $client = static::createClient();
        $client->request('GET', '/admin/docs');
    }

    /**
     * @test
     */
    public function renderingWorksIfCanNotConnectGraylog()
    {
        $graylogClientProphecy = $this->prophesize(GraylogClient::class);
        TestDoubleBundle::addProphecy(GraylogClient::class, $graylogClientProphecy);
        $graylogClientProphecy->get(Argument::cetera())->shouldBeCalled()->willThrow(
            new ConnectException('testing', new Request('GET', ''))
        );

        $client = static::createClient();
        $client->request('GET', '/admin/docs');
    }

    /**
     * @test
     */
    public function bambooDocsFluidVhFormIsRendered()
    {
        $client = static::createClient();
        $client->request('GET', '/admin/docs');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertRegExp('/Trigger Fluid View Helper Reference rendering and deployment/', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function bambooDocsFluidVhCanBeTriggered()
    {
        // Bamboo client double for the first request
        TestDoubleBundle::addProphecy(BambooClient::class, $this->prophesize(BambooClient::class));
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/docs');

        // Bamboo client double for the second request
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->post(Argument::cetera())->willReturn(
            new Response(200, [], json_encode(['buildResultKey' => 'CORE-DRF-123']))
        );

        // Get the rendered form, feed it with some data and submit it
        $form = $crawler->selectButton('Trigger bamboo')->form();
        $client->submit($form);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        // The build key is shown
        $this->assertRegExp('/CORE-DRF-123/', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function bambooDocsFluidVhReturnsErrorIfBambooClientDoesNotCreateBuild()
    {
        // Bamboo client double for the first request
        TestDoubleBundle::addProphecy(BambooClient::class, $this->prophesize(BambooClient::class));
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/docs');

        // Bamboo client double for the second request
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        // Simulate bamboo did not trigger a build - buildResultKey missing in response
        $bambooClientProphecy->post(Argument::cetera())->willReturn(
            new Response(200, [], json_encode([]))
        );

        // Get the rendered form, feed it with some data and submit it
        $form = $crawler->selectButton('Trigger bamboo')->form();
        $client->submit($form);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertRegExp('/Bamboo trigger not successful/', $client->getResponse()->getContent());
    }
}
