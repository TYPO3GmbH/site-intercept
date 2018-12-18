<?php
declare(strict_types = 1);
namespace App\Tests\Functional;

use App\Bundle\TestDoubleBundle;
use App\Client\BambooClient;
use App\Client\GerritClient;
use App\Client\SlackClient;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\Tools\SchemaTool;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class BambooPostBuildControllerTest extends TestCase
{
    /**
     * @var Connection
     */
    private static $dbConnection;

    /**
     * Ensure db is properly set up (once for all tests in this test case)
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        $kernel = new \App\Kernel('test', true);
        $kernel->boot();
        static::$dbConnection = $kernel->getContainer()->get('doctrine.dbal.default_connection');
        $entityManager = $kernel->getContainer()->get('doctrine.orm.entity_manager');
        $metaData = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->updateSchema($metaData);
        $kernel->shutdown();
    }

    /**
     * Delete all tables from database again
     */
    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
        static::dropAllTables();
    }

    private static function dropAllTables()
    {
        foreach(static::$dbConnection->getSchemaManager()->listTableNames() as $tableName) {
            static::$dbConnection->exec('DELETE FROM ' . $tableName);
        }
    }

    /**
     * @test
     */
    public function gerritVoteIsCalled()
    {
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        $bambooClientProphecy
            ->get(
                file_get_contents(__DIR__ . '/Fixtures/BambooPostBuildGoodBambooDetailsUrl.txt'),
                require __DIR__ . '/Fixtures/BambooPostBuildGoodBambooDetailsHeader.php'
            )->shouldBeCalled()
            ->willReturn(require __DIR__ . '/Fixtures/BambooPostBuildGoodBambooDetailsResponse.php');
        TestDoubleBundle::addProphecy('App\Client\BambooClient', $bambooClientProphecy);

        $gerritClientProphecy = $this->prophesize(GerritClient::class);
        $gerritClientProphecy
            ->post(
                file_get_contents(__DIR__ . '/Fixtures/BambooPostBuildGoodGerritPostUrl.txt'),
                require __DIR__ . '/Fixtures/BambooPostBuildGoodGerritPostData.php'
            )
            ->shouldBeCalled()
            ->willReturn(new Response());
        TestDoubleBundle::addProphecy(GerritClient::class, $gerritClientProphecy);

        $kernel = new \App\Kernel('test', true);
        $kernel->boot();
        $request = require __DIR__ . '/Fixtures/BambooPostBuildGoodRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }

    /**
     * @test
     */
    public function buildForFailedNightlyIsReTriggered()
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
        TestDoubleBundle::addProphecy('App\Client\BambooClient', $bambooClientProphecy);

        $kernel = new \App\Kernel('test', true);
        $kernel->boot();
        $request = require __DIR__ . '/Fixtures/BambooPostBuildFailedNightlyBuildRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }

    /**
     * @test
     * @depends buildForFailedNightlyIsReTriggered
     */
    public function slackMessageForFailedNightlyIsSendForSecondFail()
    {
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        $bambooClientProphecy
            ->get(
                'latest/result/CORE-GTN-585?os_authType=basic&expand=labels',
                Argument::cetera()
            )->shouldBeCalled()
            ->willReturn(require __DIR__ . '/Fixtures/BambooPostBuildFailedNightlyBambooDetailsResponse.php');
        TestDoubleBundle::addProphecy('App\Client\BambooClient', $bambooClientProphecy);

        $slackClientProphecy = $this->prophesize(SlackClient::class);
        $slackClientProphecy->post(Argument::cetera())->shouldBeCalled()->willReturn(new Response());
        TestDoubleBundle::addProphecy(SlackClient::class, $slackClientProphecy);

        $kernel = new \App\Kernel('test', true);
        $kernel->boot();
        $request = require __DIR__ . '/Fixtures/BambooPostBuildFailedNightlyBuildRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }
}
