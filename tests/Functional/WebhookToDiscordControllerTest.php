<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Functional;

use App\Bundle\TestDoubleBundle;
use App\Client\GeneralClient;
use App\Discord\DiscordTransformerFactory;
use App\Entity\DiscordChannel;
use App\Entity\DiscordWebhook;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Tools\SchemaTool;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class WebhookToDiscordControllerTest extends KernelTestCase
{
    /**
     * @var Connection
     */
    private static $dbConnection;

    /**
     * Ensure db is properly set up (once for all tests in this test case)
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        $kernel = new \App\Kernel('test', true);
        $kernel->boot();
        static::$dbConnection = $kernel->getContainer()->get('doctrine.dbal.default_connection');
        $entityManager = $kernel->getContainer()->get('doctrine.orm.entity_manager');
        $metaData = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->updateSchema($metaData);
        $channel = (new DiscordChannel())
            ->setChannelName('test')
            ->setChannelType(DiscordChannel::CHANNEL_TYPE_TEXT)
            ->setChannelId('123')
            ->setWebhookUrl('https://discordapp.com/api/webhooks/123/test');
        $entityManager->persist($channel);
        $entityManager->flush();
        $hook = (new DiscordWebhook())
            ->setName('test')
            ->setType(DiscordTransformerFactory::TYPE_BAMBOO)
            ->setChannel($channel)
            ->setIdentifier('1234test')
            ->setUsername('Test');
        $entityManager->persist($hook);
        $hook = (new DiscordWebhook())
            ->setName('test')
            ->setType(DiscordTransformerFactory::TYPE_BAMBOO)
            ->setIdentifier('hasnochannel')
            ->setUsername('Test');
        $entityManager->persist($hook);
        $entityManager->flush();
        $kernel->shutdown();
    }

    /**
     * Delete all tables from database again
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        static::dropAllTables();
    }

    private static function dropAllTables(): void
    {
        foreach (static::$dbConnection->getSchemaManager()->listTableNames() as $tableName) {
            static::$dbConnection->exec('DELETE FROM ' . $tableName);
        }
    }

    /**
     * @test
     */
    public function discordWebhookIsTriggered()
    {
        $generalClientProphecy = $this->prophesize(GeneralClient::class);
        $generalClientProphecy
            ->request('POST', 'https://discordapp.com/api/webhooks/123/test', Argument::any())
            ->shouldBeCalled()
            ->willReturn(new Response(200, []));
        TestDoubleBundle::addProphecy(GeneralClient::class, $generalClientProphecy);

        $kernel = new \App\Kernel('test', true);
        $kernel->boot();

        $request = require __DIR__ . '/Fixtures/BambooToDiscordGoodRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }

    /**
     * @test
     */
    public function discordWebhookIsNotFound()
    {
        $kernel = new \App\Kernel('test', true);
        $kernel->boot();

        $request = require __DIR__ . '/Fixtures/BambooToDiscordNotFoundRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        $this->assertEquals(404, $response->getStatusCode());
    }

    /**
     * @test
     */
    public function discordWebhookHasNoChannelReturnsPreconditionFailed()
    {
        $kernel = new \App\Kernel('test', true);
        $kernel->boot();

        $request = require __DIR__ . '/Fixtures/BambooToDiscordHasNoChannelRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        $this->assertEquals(412, $response->getStatusCode());
    }
}
