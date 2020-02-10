<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Functional\AdminInterface;

use App\Bundle\TestDoubleBundle;
use App\Client\BambooClient;
use App\Client\GerritClient;
use App\Client\GraylogClient;
use App\Tests\Functional\AbstractFunctionalWebTestCase;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;

class BambooCoreSecurityControllerTest extends AbstractFunctionalWebTestCase
{
    /**
     * @test
     */
    public function bambooOfflineStatusIsRendered()
    {
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->get(Argument::cetera())->willThrow(
            new RequestException('testing', new Request('GET', ''))
        );
        $client = static::createClient();
        $this->logInAsAdmin($client);
        $client->request('GET', '/admin/bamboo/core/security');
        $this->assertStringContainsString('data-rabbit-status="offline"', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function bambooOnlineStatusIsRendered()
    {
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->get('latest/agent/remote?os_authType=basic', Argument::cetera())->willReturn(
            new Response(200, [], json_encode([
                [
                    'enabled' => true,
                    'busy' => true,
                ],
                [
                    'enabled' => true,
                    'busy' => false
                ]
            ]))
        );
        $bambooClientProphecy->get('latest/queue?os_authType=basic', Argument::cetera())->willReturn(
            new Response(200, [], json_encode([
                'queuedBuilds' => [
                    'size' => 3
                ]
            ]))
        );
        $client = static::createClient();
        $this->logInAsAdmin($client);
        $client->request('GET', '/admin/bamboo/core/security');
        $this->assertStringContainsString('data-bamboo-status="online"', $client->getResponse()->getContent());
        $this->assertStringContainsString('data-bamboo-agents-available="2"', $client->getResponse()->getContent());
        $this->assertStringContainsString('data-bamboo-agents-busy="1"', $client->getResponse()->getContent());
        $this->assertStringContainsString('data-bamboo-queued="3"', $client->getResponse()->getContent());
    }

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
                            'ctxt_type' => 'triggerBamboo',
                            'env' => 'prod',
                            'level' => 6,
                            'message' => 'my message',
                            'ctxt_branch' => 'master',
                            'ctxt_change' => 12345,
                            'ctxt_patch' => 2,
                            'timestamp' => '2018-12-16T22:07:04.815Z',
                            'ctxt_bambooKey' => 'CORE-GTC-1234',
                            'ctxt_vote' => '-1',
                            'ctxt_triggeredBy' => 'interface',
                        ]
                    ]
                ]
            ]))
        );

        $client = static::createClient();
        $this->logInAsAdmin($client);
        $client->request('GET', '/admin/bamboo/core/security');
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
        $this->logInAsAdmin($client);
        $client->request('GET', '/admin/bamboo/core/security');
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
        $this->logInAsAdmin($client);
        $client->request('GET', '/admin/bamboo/core/security');
    }

    /**
     * @test
     */
    public function bambooCoreFormIsRendered()
    {
        $client = static::createClient();
        $this->logInAsAdmin($client);
        $client->request('GET', '/admin/bamboo/core/security');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertRegExp('/Trigger bamboo security builds/', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function bambooCoreCanBeTriggered()
    {
        // Bamboo client double for the first request
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->get(Argument::cetera())->willThrow(
            new RequestException('testing', new Request('GET', ''))
        );
        $client = static::createClient();
        $this->logInAsAdmin($client);
        $crawler = $client->request('GET', '/admin/bamboo/core/security');

        // Bamboo client double for the second request
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->get(Argument::cetera())->willThrow(
            new RequestException('testing', new Request('GET', ''))
        );
        $bambooClientProphecy->post(Argument::cetera())->willReturn(
            new Response(200, [], json_encode(['buildResultKey' => 'CORE-GTS-123456']))
        );

        // Get the rendered form, feed it with some data and submit it
        $form = $crawler->selectButton('bamboo_core_security_trigger_form[master]')->form();
        $form['bamboo_core_security_trigger_form[change]'] = '58920';
        $form['bamboo_core_security_trigger_form[set]'] = 3;
        $client->submit($form);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        // The build key is shown
        $this->assertRegExp('/CORE-GTS-123456/', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function bambooCoreTriggeredReturnsErrorIfBambooClientDoesNotCreateBuild()
    {
        // Bamboo client double for the first request
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->get(Argument::cetera())->willThrow(
            new RequestException('testing', new Request('GET', ''))
        );
        $client = static::createClient();
        $this->logInAsAdmin($client);
        $crawler = $client->request('GET', '/admin/bamboo/core/security');

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
        $form = $crawler->selectButton('bamboo_core_security_trigger_form[master]')->form();
        $form['bamboo_core_security_trigger_form[change]'] = '58920';
        $form['bamboo_core_security_trigger_form[set]'] = 3;
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
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->get(Argument::cetera())->willThrow(
            new RequestException('testing', new Request('GET', ''))
        );
        $client = static::createClient();
        $this->logInAsAdmin($client);
        $crawler = $client->request('GET', '/admin/bamboo/core/security');

        // Bamboo client double for the second request
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->get(Argument::cetera())->willThrow(
            new RequestException('testing', new Request('GET', ''))
        );
        $bambooClientProphecy->post(Argument::cetera())->willReturn(
            new Response(200, [], json_encode([]))
        );

        $form = $crawler->selectButton('bamboo_core_security_trigger_form[master]')->form();
        // Empty change is not allowed
        $form['bamboo_core_security_trigger_form[change]'] = '';
        $form['bamboo_core_security_trigger_form[set]'] = 3;
        $client->submit($form);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertRegExp('/Could not determine a changeId/', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function bambooCoreCanBeTriggeredByUrl()
    {
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->get(Argument::cetera())->willThrow(
            new RequestException('testing', new Request('GET', ''))
        );
        TestDoubleBundle::addProphecy(GerritClient::class, $this->prophesize(GerritClient::class));
        $client = static::createClient();
        $this->logInAsAdmin($client);
        $crawler = $client->request('GET', '/admin/bamboo/core/security');

        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->get(Argument::cetera())->willThrow(
            new RequestException('testing', new Request('GET', ''))
        );
        $bambooClientProphecy->post(Argument::cetera())->shouldBeCalled()->willReturn(
            new Response(200, [], json_encode(['buildResultKey' => 'CORE-GTS-123456']))
        );

        $gerritClientProphecy = $this->prophesize(GerritClient::class);
        TestDoubleBundle::addProphecy(GerritClient::class, $gerritClientProphecy);
        $gerritClientProphecy->get(Argument::cetera())->shouldBeCalled()->willReturn(
            new Response(200, [], json_encode([
                'project' => 'Teams/Security/TYPO3v4-Core',
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
        $form['bamboo_core_security_by_url_trigger_form[url]'] = 'https://review.typo3.org/#/c/58920/';
        $client->submit($form);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        // The build key is shown
        $this->assertRegExp('/CORE-GTS-123456/', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function bambooCoreCanBeTriggeredByUrlWithPatchSet()
    {
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->get(Argument::cetera())->willThrow(
            new RequestException('testing', new Request('GET', ''))
        );
        TestDoubleBundle::addProphecy(GerritClient::class, $this->prophesize(GerritClient::class));
        $client = static::createClient();
        $this->logInAsAdmin($client);
        $crawler = $client->request('GET', '/admin/bamboo/core/security');

        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->get(Argument::cetera())->willThrow(
            new RequestException('testing', new Request('GET', ''))
        );
        $bambooClientProphecy->post(Argument::cetera())->shouldBeCalled()->willReturn(
            new Response(200, [], json_encode(['buildResultKey' => 'CORE-GTS-123456']))
        );

        $gerritClientProphecy = $this->prophesize(GerritClient::class);
        TestDoubleBundle::addProphecy(GerritClient::class, $gerritClientProphecy);
        $gerritClientProphecy->get(Argument::cetera())->shouldBeCalled()->willReturn(
            new Response(200, [], json_encode([
                'project' => 'Teams/Security/TYPO3v4-Core',
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
        $form['bamboo_core_security_by_url_trigger_form[url]'] = 'https://review.typo3.org/#/c/58920/2';
        $client->submit($form);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        // The build key is shown
        $this->assertRegExp('/CORE-GTS-123456/', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function bambooCoreByUrlHandlesGerritException()
    {
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->get(Argument::cetera())->willThrow(
            new RequestException('testing', new Request('GET', ''))
        );
        TestDoubleBundle::addProphecy(GerritClient::class, $this->prophesize(GerritClient::class));
        $client = static::createClient();
        $this->logInAsAdmin($client);
        $crawler = $client->request('GET', '/admin/bamboo/core/security');

        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->get(Argument::cetera())->willThrow(
            new RequestException('testing', new Request('GET', ''))
        );

        $gerritClientProphecy = $this->prophesize(GerritClient::class);
        TestDoubleBundle::addProphecy(GerritClient::class, $gerritClientProphecy);
        $gerritClientProphecy->get(Argument::cetera())->willThrow(
            new ClientException('testing', new Request('GET', ''))
        );

        // Get the rendered form, feed it with some data and submit it
        $form = $crawler->selectButton('Trigger bamboo')->form();
        $form['bamboo_core_security_by_url_trigger_form[url]'] = 'https://review.typo3.org/#/c/58920/2';
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
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->get(Argument::cetera())->willThrow(
            new RequestException('testing', new Request('GET', ''))
        );
        TestDoubleBundle::addProphecy(GerritClient::class, $this->prophesize(GerritClient::class));
        $client = static::createClient();
        $this->logInAsAdmin($client);
        $crawler = $client->request('GET', '/admin/bamboo/core/security');

        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->get(Argument::cetera())->willThrow(
            new RequestException('testing', new Request('GET', ''))
        );

        $gerritClientProphecy = $this->prophesize(GerritClient::class);
        TestDoubleBundle::addProphecy(GerritClient::class, $gerritClientProphecy);
        $gerritClientProphecy->get(Argument::cetera())->shouldBeCalled()->willReturn(
            new Response(200, [], json_encode([
                'project' => 'Teams/Security/TYPO3v4-Core',
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
        $form['bamboo_core_security_by_url_trigger_form[url]'] = 'https://review.typo3.org/#/c/58920/2';
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
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->get(Argument::cetera())->willThrow(
            new RequestException('testing', new Request('GET', ''))
        );
        TestDoubleBundle::addProphecy(GerritClient::class, $this->prophesize(GerritClient::class));
        $client = static::createClient();
        $this->logInAsAdmin($client);
        $crawler = $client->request('GET', '/admin/bamboo/core/security');

        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->get(Argument::cetera())->willThrow(
            new RequestException('testing', new Request('GET', ''))
        );

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
        $form['bamboo_core_security_by_url_trigger_form[url]'] = 'https://review.typo3.org/#/c/58920/2';
        $client->submit($form);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        // The build key is shown
        $this->assertRegExp('/Trigger not successful/', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function bambooCoreByUrlHandlesNonSecurityProject()
    {
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->get(Argument::cetera())->willThrow(
            new RequestException('testing', new Request('GET', ''))
        );
        TestDoubleBundle::addProphecy(GerritClient::class, $this->prophesize(GerritClient::class));
        $client = static::createClient();
        $this->logInAsAdmin($client);
        $crawler = $client->request('GET', '/admin/bamboo/core/security');

        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->get(Argument::cetera())->willThrow(
            new RequestException('testing', new Request('GET', ''))
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
        $form['bamboo_core_security_by_url_trigger_form[url]'] = 'https://review.typo3.org/#/c/58920/2';
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
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->get(Argument::cetera())->willThrow(
            new RequestException('testing', new Request('GET', ''))
        );
        TestDoubleBundle::addProphecy(GerritClient::class, $this->prophesize(GerritClient::class));
        $client = static::createClient();
        $this->logInAsAdmin($client);
        $crawler = $client->request('GET', '/admin/bamboo/core/security');

        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->get(Argument::cetera())->willThrow(
            new RequestException('testing', new Request('GET', ''))
        );
        $bambooClientProphecy->post(Argument::cetera())->shouldBeCalled()->willReturn(
            new Response(200, [], json_encode([]))
        );

        $gerritClientProphecy = $this->prophesize(GerritClient::class);
        TestDoubleBundle::addProphecy(GerritClient::class, $gerritClientProphecy);
        $gerritClientProphecy->get(Argument::cetera())->shouldBeCalled()->willReturn(
            new Response(200, [], json_encode([
                'project' => 'Teams/Security/TYPO3v4-Core',
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
        $form['bamboo_core_security_by_url_trigger_form[url]'] = 'https://review.typo3.org/#/c/58920/';
        $client->submit($form);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        // The build key is shown
        $this->assertRegExp('/Bamboo trigger not successful/', $client->getResponse()->getContent());
    }
}
