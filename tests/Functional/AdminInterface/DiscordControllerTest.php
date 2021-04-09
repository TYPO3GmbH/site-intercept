<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Functional\AdminInterface;

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use App\Kernel;
use App\Tests\Functional\AbstractFunctionalWebTestCase;
use App\Tests\Functional\DatabasePrimer;
use Prophecy\PhpUnit\ProphecyTrait;

class DiscordControllerTest extends AbstractFunctionalWebTestCase
{
    use ProphecyTrait;

    public function setUp(): void
    {
        $kernel = new Kernel('test', true);
        $kernel->boot();
        DatabasePrimer::prime($kernel);
        $kernel->shutdown();
        $this->addRabbitManagementClientProphecy();
    }

    /**
     * @test
     */
    public function discordWebhookIndexIsRendered()
    {
        $this->addBambooClientProphecy();
        $this->addBambooClientProphecy();
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
        $this->addBambooClientProphecy();
        $this->addBambooClientProphecy();
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
        $this->addBambooClientProphecy();
        $this->addBambooClientProphecy();
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
        $this->addBambooClientProphecy();
        $this->addBambooClientProphecy();
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
        $this->addBambooClientProphecy();
        $this->addBambooClientProphecy();
        $client = static::createClient();
        $this->logInAsAdmin($client);
        $client->request('GET', '/admin/discord/howto');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
