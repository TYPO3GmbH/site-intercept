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
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

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

        $firewallName = 'main';
        $firewallContext = 'main';

        $roles = [
            'ROLE_USER',
            'ROLE_DOCUMENTATION_MAINTAINER',
        ];
        $token = new UsernamePasswordToken('admin', null, $firewallName, $roles);
        $session->set('_security_' . $firewallContext, serialize($token));
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

        $firewallName = 'main';
        $firewallContext = 'main';

        $roles = [
            'ROLE_ADMIN',
        ];
        $token = new UsernamePasswordToken('admin', null, $firewallName, $roles);
        $session->set('_security_' . $firewallContext, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $client->getCookieJar()->set($cookie);
    }
}
