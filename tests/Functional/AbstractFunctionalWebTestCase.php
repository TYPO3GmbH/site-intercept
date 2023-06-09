<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;
use T3G\Bundle\Keycloak\Security\KeyCloakUser;
use T3G\LibTestHelper\Database\DatabasePrimer;

abstract class AbstractFunctionalWebTestCase extends WebTestCase
{
    use DatabasePrimer;
    protected KernelBrowser $client;

    /**
     * Log in a client as documentation maintainer.
     */
    protected function logInAsDocumentationMaintainer(AbstractBrowser $client): void
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
     * Log in a client as admin.
     */
    protected function logInAsAdmin(AbstractBrowser $client): void
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

    protected function rebootState(): void
    {
        static::ensureKernelShutdown();

        $this->client = static::createClient();
        static::$kernel->boot();
    }
}
