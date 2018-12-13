<?php
declare(strict_types = 1);
namespace App\Tests\Functional;

use App\Bundle\TestDoubleBundle;
use App\Client\BambooClient;
use App\Client\GerritClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
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
        TestDoubleBundle::addProphecy(BambooClient::class, $this->prophesize(BambooClient::class));
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/bamboo/core');

        // Bamboo client double for the second request
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
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
        TestDoubleBundle::addProphecy(BambooClient::class, $this->prophesize(BambooClient::class));
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/bamboo/core');

        // Bamboo client double for the second request
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
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
        TestDoubleBundle::addProphecy(BambooClient::class, $this->prophesize(BambooClient::class));
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/bamboo/core');

        // Bamboo client double for the second request
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
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

    /**
     * @test
     */
    public function bambooCoreCanBeTriggeredByUrl()
    {
        TestDoubleBundle::addProphecy(BambooClient::class, $this->prophesize(BambooClient::class));
        TestDoubleBundle::addProphecy(GerritClient::class, $this->prophesize(GerritClient::class));
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/bamboo/core');

        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->post(Argument::cetera())->shouldBeCalled()->willReturn(
            new Response(200, [], json_encode(['buildResultKey' => 'CORE-GTC-123456']))
        );

        $gerritClientProphecy = $this->prophesize(GerritClient::class);
        TestDoubleBundle::addProphecy(GerritClient::class, $gerritClientProphecy);
        $gerritClientProphecy->get(Argument::cetera())->shouldBeCalled()->willReturn(
            new Response(200, [], json_encode([
                'project' => 'Packages/TYPO3.CMS',
                'branch' => 'master',
                'current_revision' => '12345',
                'revisions' => [
                    '12345' => [
                        '_number' => 3,
                    ]
                ]
            ]))
        );

        // Get the rendered form, feed it with some data and submit it
        $form = $crawler->selectButton('Trigger bamboo')->form();
        $form['bamboo_core_by_url_trigger_form[url]'] = 'https://review.typo3.org/#/c/58920/';
        $client->submit($form);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        // The build key is shown
        $this->assertRegExp('/CORE-GTC-123456/', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function bambooCoreCanBeTriggeredByUrlWithPatchSet()
    {
        TestDoubleBundle::addProphecy(BambooClient::class, $this->prophesize(BambooClient::class));
        TestDoubleBundle::addProphecy(GerritClient::class, $this->prophesize(GerritClient::class));
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/bamboo/core');

        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->post(Argument::cetera())->shouldBeCalled()->willReturn(
            new Response(200, [], json_encode(['buildResultKey' => 'CORE-GTC-123456']))
        );

        $gerritClientProphecy = $this->prophesize(GerritClient::class);
        TestDoubleBundle::addProphecy(GerritClient::class, $gerritClientProphecy);
        $gerritClientProphecy->get(Argument::cetera())->shouldBeCalled()->willReturn(
            new Response(200, [], json_encode([
                'project' => 'Packages/TYPO3.CMS',
                'branch' => 'master',
                'current_revision' => '12345',
                'revisions' => [
                    '12345' => [
                        '_number' => 2,
                    ]
                ]
            ]))
        );

        // Get the rendered form, feed it with some data and submit it
        $form = $crawler->selectButton('Trigger bamboo')->form();
        $form['bamboo_core_by_url_trigger_form[url]'] = 'https://review.typo3.org/#/c/58920/2';
        $client->submit($form);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        // The build key is shown
        $this->assertRegExp('/CORE-GTC-123456/', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function bambooCoreByUrlHandlesGerritException()
    {
        TestDoubleBundle::addProphecy(BambooClient::class, $this->prophesize(BambooClient::class));
        TestDoubleBundle::addProphecy(GerritClient::class, $this->prophesize(GerritClient::class));
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/bamboo/core');

        TestDoubleBundle::addProphecy(BambooClient::class, $this->prophesize(BambooClient::class));

        $gerritClientProphecy = $this->prophesize(GerritClient::class);
        TestDoubleBundle::addProphecy(GerritClient::class, $gerritClientProphecy);
        $gerritClientProphecy->get(Argument::cetera())->willThrow(
            new ClientException('testing', new Request('GET', ''))
        );

        // Get the rendered form, feed it with some data and submit it
        $form = $crawler->selectButton('Trigger bamboo')->form();
        $form['bamboo_core_by_url_trigger_form[url]'] = 'https://review.typo3.org/#/c/58920/2';
        $client->submit($form);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        // The build key is shown
        $this->assertRegExp('/Trigger not successful/', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function bambooCoreByUrlHandlesUnknownPatchSet()
    {
        TestDoubleBundle::addProphecy(BambooClient::class, $this->prophesize(BambooClient::class));
        TestDoubleBundle::addProphecy(GerritClient::class, $this->prophesize(GerritClient::class));
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/bamboo/core');

        TestDoubleBundle::addProphecy(BambooClient::class, $this->prophesize(BambooClient::class));

        $gerritClientProphecy = $this->prophesize(GerritClient::class);
        TestDoubleBundle::addProphecy(GerritClient::class, $gerritClientProphecy);
        $gerritClientProphecy->get(Argument::cetera())->shouldBeCalled()->willReturn(
            new Response(200, [], json_encode([
                'project' => 'Packages/TYPO3.CMS',
                'branch' => 'master',
                'current_revision' => '12345',
                'revisions' => [
                    '12345' => [
                        // only 3 exists, but 2 is requested
                        '_number' => 3,
                    ]
                ]
            ]))
        );

        // Get the rendered form, feed it with some data and submit it
        $form = $crawler->selectButton('Trigger bamboo')->form();
        $form['bamboo_core_by_url_trigger_form[url]'] = 'https://review.typo3.org/#/c/58920/2';
        $client->submit($form);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        // The build key is shown
        $this->assertRegExp('/Trigger not successful/', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function bambooCoreByUrlHandlesWrongProject()
    {
        TestDoubleBundle::addProphecy(BambooClient::class, $this->prophesize(BambooClient::class));
        TestDoubleBundle::addProphecy(GerritClient::class, $this->prophesize(GerritClient::class));
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/bamboo/core');

        TestDoubleBundle::addProphecy(BambooClient::class, $this->prophesize(BambooClient::class));

        $gerritClientProphecy = $this->prophesize(GerritClient::class);
        TestDoubleBundle::addProphecy(GerritClient::class, $gerritClientProphecy);
        $gerritClientProphecy->get(Argument::cetera())->shouldBeCalled()->willReturn(
            new Response(200, [], json_encode([
                'project' => 'Packages/NotTheProjectYouAreLookingFor',
                'branch' => 'master',
                'current_revision' => '12345',
                'revisions' => [
                    '12345' => [
                        '_number' => 2,
                    ]
                ]
            ]))
        );

        // Get the rendered form, feed it with some data and submit it
        $form = $crawler->selectButton('Trigger bamboo')->form();
        $form['bamboo_core_by_url_trigger_form[url]'] = 'https://review.typo3.org/#/c/58920/2';
        $client->submit($form);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        // The build key is shown
        $this->assertRegExp('/Trigger not successful/', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function bambooCoreByUrlHandlesBambooErrorResponse()
    {
        TestDoubleBundle::addProphecy(BambooClient::class, $this->prophesize(BambooClient::class));
        TestDoubleBundle::addProphecy(GerritClient::class, $this->prophesize(GerritClient::class));
        $client = static::createClient();
        $crawler = $client->request('GET', '/admin/bamboo/core');

        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->post(Argument::cetera())->shouldBeCalled()->willReturn(
            new Response(200, [], json_encode([]))
        );

        $gerritClientProphecy = $this->prophesize(GerritClient::class);
        TestDoubleBundle::addProphecy(GerritClient::class, $gerritClientProphecy);
        $gerritClientProphecy->get(Argument::cetera())->shouldBeCalled()->willReturn(
            new Response(200, [], json_encode([
                'project' => 'Packages/TYPO3.CMS',
                'branch' => 'master',
                'current_revision' => '12345',
                'revisions' => [
                    '12345' => [
                        '_number' => 3,
                    ]
                ]
            ]))
        );

        // Get the rendered form, feed it with some data and submit it
        $form = $crawler->selectButton('Trigger bamboo')->form();
        $form['bamboo_core_by_url_trigger_form[url]'] = 'https://review.typo3.org/#/c/58920/';
        $client->submit($form);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        // The build key is shown
        $this->assertRegExp('/Bamboo trigger not successful/', $client->getResponse()->getContent());
    }
}
