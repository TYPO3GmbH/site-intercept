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
use App\Client\GerritClient;
use App\Client\SlackClient;
use App\Kernel;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Tools\SchemaTool;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class BambooPostBuildControllerTest extends AbstractFunctionalWebTestCase
{
    use ProphecyTrait;
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
        $kernel = new Kernel('test', true);
        $kernel->boot();
        static::$dbConnection = $kernel->getContainer()->get('doctrine.dbal.default_connection');
        $entityManager = $kernel->getContainer()->get('doctrine.orm.entity_manager');
        $metaData = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->updateSchema($metaData);
        $kernel->shutdown();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->addGerritClientProphecy();
        $this->addSlackClientProphecy();
        $this->addRabbitManagementClientProphecy();
        $this->addGeneralClientProphecy();
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
    public function gerritVoteIsCalled(): void
    {
        TestDoubleBundle::reset();
        $this->addSlackClientProphecy();
        $this->addGeneralClientProphecy();
        $this->addRabbitManagementClientProphecy();
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        $bambooClientProphecy
            ->get(
                file_get_contents(__DIR__ . '/Fixtures/BambooPostBuildGoodBambooDetailsUrl.txt'),
                require __DIR__ . '/Fixtures/BambooPostBuildGoodBambooDetailsHeader.php'
            )->shouldBeCalled()
            ->willReturn(require __DIR__ . '/Fixtures/BambooPostBuildGoodBambooDetailsResponse.php');
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);

        $gerritClientProphecy = $this->prophesize(GerritClient::class);
        $gerritClientProphecy
            ->post(
                file_get_contents(__DIR__ . '/Fixtures/BambooPostBuildGoodGerritPostUrl.txt'),
                require __DIR__ . '/Fixtures/BambooPostBuildGoodGerritPostData.php'
            )
            ->shouldBeCalled()
            ->willReturn(new Response());
        TestDoubleBundle::addProphecy(GerritClient::class, $gerritClientProphecy);

        $kernel = new Kernel('test', true);
        $kernel->boot();
        $request = require __DIR__ . '/Fixtures/BambooPostBuildGoodRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }

    /**
     * @test
     */
    public function buildForFailedNightlyIsReTriggered(): void
    {
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        $bambooClientProphecy
            ->get(
                'latest/result/CORE-GTN-585?os_authType=basic&expand=labels',
                Argument::cetera()
            )->shouldBeCalled()
            ->willReturn(require __DIR__ . '/Fixtures/BambooPostBuildFailedNightlyBambooDetailsResponse.php');

        $bambooClientProphecy
            ->put(
                'latest/queue/CORE-GTN-585?os_authType=basic',
                Argument::cetera()
            )->shouldBeCalled()
            ->willReturn(new Response());
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);

        $kernel = new Kernel('test', true);
        $kernel->boot();
        $request = require __DIR__ . '/Fixtures/BambooPostBuildFailedNightlyBuildRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }

    /**
     * @test
     * @depends buildForFailedNightlyIsReTriggered
     */
    public function slackMessageForFailedNightlyIsSendForSecondFail(): void
    {
        TestDoubleBundle::reset();
        $this->addGerritClientProphecy();
        $this->addRabbitManagementClientProphecy();
        $this->addGeneralClientProphecy();
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        $bambooClientProphecy
            ->get(
                'latest/result/CORE-GTN-585?os_authType=basic&expand=labels',
                Argument::cetera()
            )->shouldBeCalled()
            ->willReturn(require __DIR__ . '/Fixtures/BambooPostBuildFailedNightlyBambooDetailsResponse.php');
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);

        $slackClientProphecy = $this->prophesize(SlackClient::class);
        $slackClientProphecy->post(Argument::cetera())->shouldBeCalled()->willReturn(new Response());
        $slackClientProphecy->post(Argument::cetera())->shouldBeCalled()->willReturn(new Response());
        TestDoubleBundle::addProphecy(SlackClient::class, $slackClientProphecy);

        $kernel = new Kernel('test', true);
        $kernel->boot();
        $request = require __DIR__ . '/Fixtures/BambooPostBuildFailedNightlyBuildRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }
}
