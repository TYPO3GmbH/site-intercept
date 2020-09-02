<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;
use T3G\Bundle\Keycloak\Security\KeyCloakUser;

abstract class AbstractFunctionalWebTestCase extends WebTestCase
{
    /**
     * Log in a client as documentation maintainer
     *
     * @param Client $client
     */
    protected function logInAsDocumentationMaintainer(Client $client)
    {
        $session = $client->getContainer()->get('session');
        $firewall = 'main';
        $token = new PostAuthenticationGuardToken(new KeyCloakUser('xX_DocsBoi_Xx', ['ROLE_OAUTH_USER', 'ROLE_USER', 'ROLE_DOCUMENTATION_MAINTAINER'], 'oelie@boelie.nl', 'Use the force, Harry ~ Gandalf'), 'keycloak.typo3.com.user.provider', ['ROLE_OAUTH_USER', 'ROLE_USER', 'ROLE_DOCUMENTATION_MAINTAINER']);

        $session->set('_security_' . $firewall, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());

        $client->getCookieJar()->set($cookie);
    }

    /**
     * Log in a client as admin
     *
     * @param Client $client
     */
    protected function logInAsAdmin(Client $client)
    {
        $session = $client->getContainer()->get('session');
        $firewall = 'main';
        $token = new PostAuthenticationGuardToken(new KeyCloakUser('Oelie Boelie', ['ROLE_OAUTH_USER', 'ROLE_USER', 'ROLE_ADMIN'], 'oelie@boelie.nl', 'Oelie Boelie'), 'keycloak.typo3.com.user.provider', ['ROLE_OAUTH_USER', 'ROLE_USER', 'ROLE_ADMIN']);

        $session->set('_security_' . $firewall, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());

        $client->getCookieJar()->set($cookie);
    }
}
