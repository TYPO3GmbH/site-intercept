<?php

declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Functional;

use App\Bundle\TestDoubleBundle;
use App\Client\BambooClient;
use App\Client\GeneralClient;
use App\Client\GerritClient;
use App\Client\GraylogClient;
use App\Client\PackagistClient;
use App\Client\RabbitManagementClient;
use App\Client\SlackClient;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;
use T3G\Bundle\Keycloak\Security\KeyCloakUser;

abstract class AbstractFunctionalWebTestCase extends WebTestCase
{
    /**
     * Log in a client as documentation maintainer
     *
     * @param AbstractBrowser $client
     */
    protected function logInAsDocumentationMaintainer(AbstractBrowser $client)
    {
        $session = $client->getContainer()->get('session');
        $firewall = 'main';
        $token = new PostAuthenticationGuardToken(
            new KeyCloakUser('xX_DocsBoi_Xx', ['ROLE_OAUTH_USER', 'ROLE_USER', 'ROLE_DOCUMENTATION_MAINTAINER'], 'oelie@boelie.nl', 'Use the force, Harry ~ Gandalf'),
            'keycloak.typo3.com.user.provider',
            ['ROLE_OAUTH_USER', 'ROLE_USER', 'ROLE_DOCUMENTATION_MAINTAINER']
        );

        $session->set('_security_' . $firewall, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());

        $client->getCookieJar()->set($cookie);
    }

    /**
     * Log in a client as admin
     *
     * @param AbstractBrowser $client
     */
    protected function logInAsAdmin(AbstractBrowser $client)
    {
        $session = $client->getContainer()->get('session');
        $firewall = 'main';
        $token = new PostAuthenticationGuardToken(
            new KeyCloakUser('Oelie Boelie', ['ROLE_OAUTH_USER', 'ROLE_USER', 'ROLE_ADMIN'], 'oelie@boelie.nl', 'Oelie Boelie'),
            'keycloak.typo3.com.user.provider',
            ['ROLE_OAUTH_USER', 'ROLE_USER', 'ROLE_ADMIN']
        );

        $session->set('_security_' . $firewall, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());

        $client->getCookieJar()->set($cookie);
    }

    protected function addGerritClientProphecy(): ObjectProphecy
    {
        $gerritClient = $this->prophesize(GerritClient::class);
        TestDoubleBundle::addProphecy(GerritClient::class, $gerritClient);
        return $gerritClient;
    }

    protected function addBambooClientProphecy(): ObjectProphecy
    {
        $bambooClient = $this->prophesize(BambooClient::class);
        $bambooClient->get('latest/agent/remote?os_authType=basic', Argument::cetera())->willReturn(new Response());
        $bambooClient->get('latest/queue?os_authType=basic', Argument::cetera())->willReturn(new Response());
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClient);
        return $bambooClient;
    }

    protected function addGraylogClientProphecy(): ObjectProphecy
    {
        $gray = $this->prophesize(GraylogClient::class);
        TestDoubleBundle::addProphecy(GraylogClient::class, $gray);
        return $gray;
    }

    protected function addRabbitManagementClientProphecy(): ObjectProphecy
    {
        $prophecy = $this->prophesize(RabbitManagementClient::class);
        $prophecy->get(Argument::containingString('api/queues/%2f/'), Argument::cetera())->willReturn(new Response(200, [], '{}'));
        TestDoubleBundle::addProphecy(RabbitManagementClient::class, $prophecy);
        return $prophecy;
    }

    protected function addPackagistClientProphecy(): ObjectProphecy
    {
        $prophecy = $this->prophesize(PackagistClient::class);
        TestDoubleBundle::addProphecy(PackagistClient::class, $prophecy);
        return $prophecy;
    }

    protected function addSlackClientProphecy(): ObjectProphecy
    {
        $prophecy = $this->prophesize(SlackClient::class);
        TestDoubleBundle::addProphecy(SlackClient::class, $prophecy);
        return $prophecy;
    }

    protected function addGeneralClientProphecy(): ObjectProphecy
    {
        $prophecy = $this->prophesize(GeneralClient::class);
        TestDoubleBundle::addProphecy(GeneralClient::class, $prophecy);
        return $prophecy;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        TestDoubleBundle::reset();
    }
}
