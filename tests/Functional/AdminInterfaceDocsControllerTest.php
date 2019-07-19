<?php
declare(strict_types = 1);
namespace App\Tests\Functional;

use App\Bundle\TestDoubleBundle;
use App\Client\BambooClient;
use App\Client\GerritClient;
use App\Client\GraylogClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;

class AdminInterfaceDocsControllerTest extends AbstractFunctionalWebTestCase
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
        $response = $client->getResponse()->getContent();
        $this->assertRegExp('/12345/', $response);
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
        $this->logInAsDocumentationMaintainer($client);
        $client->request('GET', '/admin/docs');
        $response = $client->getResponse()->getContent();
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertRegExp('/Render Fluid View Helper Reference 9.5/', $response);
    }

    /**
     * @test
     */
    public function bambooDocsSurf20FormIsRendered()
    {
        $client = static::createClient();
        $this->logInAsDocumentationMaintainer($client);
        $client->request('GET', '/admin/docs');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertRegExp('/Render TYPO3 Surf 2.0 Documentation/', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function bambooDocsSurfMasterFormIsRendered()
    {
        $client = static::createClient();
        $this->logInAsDocumentationMaintainer($client);
        $client->request('GET', '/admin/docs');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertRegExp('/Render TYPO3 Surf Master Documentation/', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function bambooDocsFluidVhCanBeTriggered()
    {
        // Bamboo client double for the first request
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->get(Argument::cetera())->willThrow(
            new RequestException('testing', new Request('GET', ''))
        );
        $client = static::createClient();
        $this->logInAsDocumentationMaintainer($client);
        $crawler = $client->request('GET', '/admin/docs');

        // Bamboo client double for the second request
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->get(Argument::cetera())->willThrow(
            new RequestException('testing', new Request('GET', ''))
        );
        $bambooClientProphecy->post(Argument::cetera())->willReturn(
            new Response(200, [], json_encode(['buildResultKey' => 'CORE-DRF-123']))
        );

        // Get the rendered form, feed it with some data and submit it
        $form = $crawler->selectButton('Render Fluid View Helper Reference 9.5')->form();
        $client->submit($form);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        // The build key is shown
        $this->assertRegExp('/CORE-DRF-123/', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function bambooDocsSurf20CanBeTriggered()
    {
        // Bamboo client double for the first request
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->get(Argument::cetera())->willThrow(
            new RequestException('testing', new Request('GET', ''))
        );
        $client = static::createClient();
        $this->logInAsDocumentationMaintainer($client);
        $crawler = $client->request('GET', '/admin/docs');

        // Bamboo client double for the second request
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->get(Argument::cetera())->willThrow(
            new RequestException('testing', new Request('GET', ''))
        );
        $bambooClientProphecy->post(Argument::cetera())->willReturn(
            new Response(200, [], json_encode(['buildResultKey' => 'CORE-DRS-123']))
        );

        // Get the rendered form, feed it with some data and submit it
        $form = $crawler->selectButton('Render TYPO3 Surf 2.0 Documentation')->form();
        $client->submit($form);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        // The build key is shown
        $this->assertRegExp('/CORE-DRS-123/', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function bambooDocsSurfMasterCanBeTriggered()
    {
        // Bamboo client double for the first request
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->get(Argument::cetera())->willThrow(
            new RequestException('testing', new Request('GET', ''))
        );
        $client = static::createClient();
        $this->logInAsDocumentationMaintainer($client);
        $crawler = $client->request('GET', '/admin/docs');

        // Bamboo client double for the second request
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->get(Argument::cetera())->willThrow(
            new RequestException('testing', new Request('GET', ''))
        );
        $bambooClientProphecy->post(Argument::cetera())->willReturn(
            new Response(200, [], json_encode(['buildResultKey' => 'CORE-DRSM-123']))
        );

        // Get the rendered form, feed it with some data and submit it
        $form = $crawler->selectButton('Render TYPO3 Surf Master Documentation')->form();
        $client->submit($form);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        // The build key is shown
        $this->assertRegExp('/CORE-DRSM-123/', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function bambooDocsFluidVhReturnsErrorIfBambooClientDoesNotCreateBuild()
    {
        // Bamboo client double for the first request
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->get(Argument::cetera())->willThrow(
            new RequestException('testing', new Request('GET', ''))
        );
        $client = static::createClient();
        $this->logInAsDocumentationMaintainer($client);
        $crawler = $client->request('GET', '/admin/docs');

        // Bamboo client double for the second request
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->get(Argument::cetera())->willThrow(
            new RequestException('testing', new Request('GET', ''))
        );
        // Simulate bamboo did not trigger a build - buildResultKey missing in response
        $bambooClientProphecy->post(Argument::cetera())->willReturn(
            new Response(200, [], json_encode([]))
        );

        // Get the rendered form, feed it with some data and submit it
        $form = $crawler->selectButton('Render Fluid View Helper Reference 9.5')->form();
        $client->submit($form);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertRegExp('/Bamboo trigger not successful/', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function bambooDocsSurf20ReturnsErrorIfBambooClientDoesNotCreateBuild()
    {
        // Bamboo client double for the first request
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->get(Argument::cetera())->willThrow(
            new RequestException('testing', new Request('GET', ''))
        );
        $client = static::createClient();
        $this->logInAsDocumentationMaintainer($client);
        $crawler = $client->request('GET', '/admin/docs');

        // Bamboo client double for the second request
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->get(Argument::cetera())->willThrow(
            new RequestException('testing', new Request('GET', ''))
        );
        // Simulate bamboo did not trigger a build - buildResultKey missing in response
        $bambooClientProphecy->post(Argument::cetera())->willReturn(
            new Response(200, [], json_encode([]))
        );

        // Get the rendered form, feed it with some data and submit it
        $form = $crawler->selectButton('Render TYPO3 Surf 2.0 Documentation')->form();
        $client->submit($form);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertRegExp('/Bamboo trigger not successful/', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function bambooDocsSurfMasterReturnsErrorIfBambooClientDoesNotCreateBuild()
    {
        // Bamboo client double for the first request
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->get(Argument::cetera())->willThrow(
            new RequestException('testing', new Request('GET', ''))
        );
        $client = static::createClient();
        $this->logInAsDocumentationMaintainer($client);
        $crawler = $client->request('GET', '/admin/docs');

        // Bamboo client double for the second request
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->get(Argument::cetera())->willThrow(
            new RequestException('testing', new Request('GET', ''))
        );
        // Simulate bamboo did not trigger a build - buildResultKey missing in response
        $bambooClientProphecy->post(Argument::cetera())->willReturn(
            new Response(200, [], json_encode([]))
        );

        // Get the rendered form, feed it with some data and submit it
        $form = $crawler->selectButton('Render TYPO3 Surf Master Documentation')->form();
        $client->submit($form);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertRegExp('/Bamboo trigger not successful/', $client->getResponse()->getContent());
    }
}
