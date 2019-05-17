<?php
declare(strict_types = 1);
namespace App\Tests\Functional;

use App\Bundle\TestDoubleBundle;
use App\Client\BambooClient;
use App\Client\GerritClient;
use App\Client\SlackClient;
use App\Entity\DocumentationJar;
use App\Enum\DocumentationStatus;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
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
        foreach(static::$dbConnection->getSchemaManager()->listTableNames() as $tableName) {
            static::$dbConnection->exec('DELETE FROM ' . $tableName);
        }
    }

    /**
     * @test
     */
    public function gerritVoteIsCalled(): void
    {
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

        $kernel = new \App\Kernel('test', true);
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
    public function slackMessageForFailedNightlyIsSendForSecondFail(): void
    {
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
        TestDoubleBundle::addProphecy(SlackClient::class, $slackClientProphecy);

        $kernel = new \App\Kernel('test', true);
        $kernel->boot();
        $request = require __DIR__ . '/Fixtures/BambooPostBuildFailedNightlyBuildRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }

    /**
     * @test
     */
    public function triggeredDeletionRemovesDatabaseRow(): void
    {
        $bambooBuildKey = 'CORE-DDEL-4711';

        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        $bambooClientProphecy
            ->get(
                'latest/result/' . $bambooBuildKey . '?os_authType=basic&expand=labels',
                Argument::cetera()
            )->shouldBeCalled()
            ->willReturn(require __DIR__ . '/Fixtures/BambooPostBuildGoodBambooDetailsDocsDeletionResponse.php');
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);

        $kernel = new \App\Kernel('test', true);
        $kernel->boot();

        /** @var EntityManager $entityManager */
        $entityManager = $kernel->getContainer()->get('doctrine.orm.entity_manager');
        $documentationJar = $this->generateRandomJar()
            ->setStatus(DocumentationStatus::STATUS_DELETING)
            ->setMinimumTypoVersion('9.5')
            ->setMaximumTypoVersion('9.5')
            ->setBuildKey($bambooBuildKey);
        $entityManager->persist($documentationJar);
        $entityManager->flush();

        $request = require __DIR__ . '/Fixtures/BambooPostBuildGoodDocsDeletionRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        $documentationJarRepository = $entityManager->getRepository(DocumentationJar::class);
        $result = $documentationJarRepository->findBy([
            'buildKey' => $bambooBuildKey,
        ]);

        $this->assertCount(0, $result);
    }

    /**
     * @test
     */
    public function triggeredDeletionFailsAndResetsStatus(): void
    {
        $bambooBuildKey = 'CORE-DDEL-4711';

        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        $bambooClientProphecy
            ->get(
                'latest/result/' . $bambooBuildKey . '?os_authType=basic&expand=labels',
                Argument::cetera()
            )->shouldBeCalled()
            ->willReturn(require __DIR__ . '/Fixtures/BambooPostBuildBadBambooDetailsDocsDeletionFailedResponse.php');
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);

        $kernel = new \App\Kernel('test', true);
        $kernel->boot();

        /** @var EntityManager $entityManager */
        $entityManager = $kernel->getContainer()->get('doctrine.orm.entity_manager');
        $documentationJar = $this->generateRandomJar()
            ->setStatus(DocumentationStatus::STATUS_DELETING)
            ->setMinimumTypoVersion('9.5')
            ->setMaximumTypoVersion('9.5')
            ->setBuildKey($bambooBuildKey);
        $entityManager->persist($documentationJar);
        $entityManager->flush();

        $request = require __DIR__ . '/Fixtures/BambooPostBuildGoodDocsDeletionRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        $documentationJarRepository = $entityManager->getRepository(DocumentationJar::class);
        $result = $documentationJarRepository->findBy([
            'packageName' => $documentationJar->getPackageName(),
            'packageType' => $documentationJar->getPackageType(),
            'branch' => $documentationJar->getBranch(),
            'status' => DocumentationStatus::STATUS_RENDERED
        ]);

        $this->assertCount(1, $result);
        $this->assertSame(DocumentationStatus::STATUS_RENDERED, current($result)->getStatus());
    }

    /**
     * @test
     */
    public function successfulRenderingSetsStatusToRendered(): void
    {
        $bambooBuildKey = 'CORE-DR-42';

        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        $bambooClientProphecy
            ->get(
                'latest/result/' . $bambooBuildKey . '?os_authType=basic&expand=labels',
                Argument::cetera()
            )->shouldBeCalled()
            ->willReturn(require __DIR__ . '/Fixtures/BambooPostBuildGoodBambooDetailsDocsRenderingResponse.php');
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);

        $kernel = new \App\Kernel('test', true);
        $kernel->boot();

        /** @var EntityManager $entityManager */
        $entityManager = $kernel->getContainer()->get('doctrine.orm.entity_manager');
        $documentationJar = $this->generateRandomJar()
            ->setStatus(DocumentationStatus::STATUS_RENDERING)
            ->setBuildKey($bambooBuildKey);
        $entityManager->persist($documentationJar);
        $entityManager->flush();

        $request = require __DIR__ . '/Fixtures/BambooPostBuildGoodDocsRenderingRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        $documentationJarRepository = $entityManager->getRepository(DocumentationJar::class);
        $result = $documentationJarRepository->findBy([
            'packageName' => $documentationJar->getPackageName(),
            'packageType' => $documentationJar->getPackageType(),
            'branch' => $documentationJar->getBranch(),
            'status' => DocumentationStatus::STATUS_RENDERED
        ]);

        $this->assertCount(1, $result);
    }

    /**
     * @test
     */
    public function failedRenderingSetsStatusToRenderingFailed(): void
    {
        $bambooBuildKey = 'CORE-DR-42';

        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        $bambooClientProphecy
            ->get(
                'latest/result/' . $bambooBuildKey . '?os_authType=basic&expand=labels',
                Argument::cetera()
            )->shouldBeCalled()
            ->willReturn(require __DIR__ . '/Fixtures/BambooPostBuildBadBambooDetailsDocsRenderingFailedResponse.php');
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);

        $kernel = new \App\Kernel('test', true);
        $kernel->boot();

        /** @var EntityManager $entityManager */
        $entityManager = $kernel->getContainer()->get('doctrine.orm.entity_manager');
        $documentationJar = $this->generateRandomJar()
            ->setStatus(DocumentationStatus::STATUS_DELETING)
            ->setBuildKey($bambooBuildKey);
        $entityManager->persist($documentationJar);
        $entityManager->flush();

        $request = require __DIR__ . '/Fixtures/BambooPostBuildGoodDocsRenderingRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        $documentationJarRepository = $entityManager->getRepository(DocumentationJar::class);
        $result = $documentationJarRepository->findBy([
            'packageName' => $documentationJar->getPackageName(),
            'packageType' => $documentationJar->getPackageType(),
            'branch' => $documentationJar->getBranch(),
            'status' => DocumentationStatus::STATUS_RENDERING_FAILED
        ]);

        $this->assertCount(1, $result);
    }

    private function generateRandomJar(): DocumentationJar
    {
        $faker = \Faker\Factory::create();
        $vendor = $faker->userName;
        $name = $faker->slug;
        $packageName = $vendor . '/' . $name;
        $branch = $faker->randomElement(['master', 'draft', '8.7']);

        $documentationJar = (new DocumentationJar())
            ->setVendor($vendor)
            ->setName($name)
            ->setPackageName($packageName)
            ->setPackageType('typo3-cms-extension')
            ->setTypeShort('c')
            ->setTypeLong('core-extension')
            ->setRepositoryUrl('https://github.com/' . $packageName . '/')
            ->setPublicComposerJsonUrl('https://raw.githubusercontent.com/' . $packageName . '/' . $branch . '/composer.json')
            ->setBranch($branch)
            ->setTargetBranchDirectory($branch);

        return $documentationJar;
    }
}
