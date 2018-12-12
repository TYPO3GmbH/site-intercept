<?php
declare(strict_types = 1);
namespace App\Tests\Functional;

use App\Bundle\TestDoubleBundle;
use App\Client\BambooClient;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminInterfaceBambooCoreControllerTest extends WebTestCase
{
    /**
     * @test
     */
    public function bambooCoreFormIsRendered()
    {
        $client = static::createClient();
        $client->request('GET', '/admin/bamboo/core');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertRegExp('/Trigger bamboo builds/', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function bambooCoreCanBeTriggered()
    {
        // Bamboo client double for the first request
        TestDoubleBundle::addProphecy('App\Client\BambooClient', $this->prophesize(BambooClient::class));
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/bamboo/core');

        // Bamboo client double for the second request
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy('App\Client\BambooClient', $bambooClientProphecy);
        $bambooClientProphecy->post(Argument::cetera())->willReturn(
            new Response(200, [], json_encode(['buildResultKey' => 'CORE-GTC-123456']))
        );

        // Get the rendered form, feed it with some data and submit it
        $form = $crawler->selectButton('Trigger master')->form();
        $form['bamboo_core_trigger_form[change]'] = '58920';
        $form['bamboo_core_trigger_form[set]'] = 3;
        $client->submit($form);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        // The build key is shown
        $this->assertRegExp('/CORE-GTC-123456/', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function bambooCoreTriggeredReturnsErrorIfBambooClientDoesNotCreateBuild()
    {
        // Bamboo client double for the first request
        TestDoubleBundle::addProphecy('App\Client\BambooClient', $this->prophesize(BambooClient::class));
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/bamboo/core');

        // Bamboo client double for the second request
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy('App\Client\BambooClient', $bambooClientProphecy);
        // Simulate bamboo did not trigger a build - buildResultKey missing in response
        $bambooClientProphecy->post(Argument::cetera())->willReturn(
            new Response(200, [], json_encode([]))
        );

        // Get the rendered form, feed it with some data and submit it
        $form = $crawler->selectButton('Trigger master')->form();
        $form['bamboo_core_trigger_form[change]'] = '58920';
        $form['bamboo_core_trigger_form[set]'] = 3;
        $client->submit($form);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertRegExp('/Bamboo trigger not successful/', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function bambooCoreTriggeredReturnsErrorIfBrokenFormIsSubmitted()
    {
        // Bamboo client double for the first request
        TestDoubleBundle::addProphecy('App\Client\BambooClient', $this->prophesize(BambooClient::class));
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/bamboo/core');

        // Bamboo client double for the second request
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy('App\Client\BambooClient', $bambooClientProphecy);
        $bambooClientProphecy->post(Argument::cetera())->willReturn(
            new Response(200, [], json_encode([]))
        );

        $form = $crawler->selectButton('Trigger master')->form();
        // Empty change is not allowed
        $form['bamboo_core_trigger_form[change]'] = '';
        $form['bamboo_core_trigger_form[set]'] = 3;
        $client->submit($form);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertRegExp('/Could not determine a changeId/', $client->getResponse()->getContent());
    }
}
