<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Functional;

class AdminInterfaceDiscordControllerTest extends AbstractFunctionalWebTestCase
{
    public function setUp()
    {
        $kernel = new \App\Kernel('test', true);
        $kernel->boot();
        DatabasePrimer::prime($kernel);
        $kernel->shutdown();
    }

    /**
     * @test
     */
    public function discordWebhookIndexIsRendered()
    {
        $client = static::createClient();
        $this->logInAsAdmin($client);
        $client->request('GET', '/admin/discord');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function discordWebhookAddFormIsRendered()
    {
        $client = static::createClient();
        $this->logInAsAdmin($client);
        $client->request('GET', '/admin/discord/webhook/add');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function discordScheduledMessagesIndexIsRendered()
    {
        $client = static::createClient();
        $this->logInAsAdmin($client);
        $client->request('GET', '/admin/discord/scheduled');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function discordScheduledMessagesAddFormIsRendered()
    {
        $client = static::createClient();
        $this->logInAsAdmin($client);
        $client->request('GET', '/admin/discord/scheduled/add');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    /**
     * @test
     */
    public function discordWebhookHowToPageIsRendered()
    {
        $client = static::createClient();
        $this->logInAsAdmin($client);
        $client->request('GET', '/admin/discord/howto');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
