<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Functional;

use App\Bundle\ClockMockBundle;
use App\Bundle\TestDoubleBundle;
use App\Client\BambooClient;
use App\Client\GeneralClient;
use App\Client\SlackClient;
use App\Entity\DocumentationJar;
use App\Extractor\DeploymentInformation;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DocsToBambooControllerTest extends KernelTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $kernel = self::bootKernel();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        ClockMockBundle::register(DeploymentInformation::class);
        ClockMockBundle::withClockMock(155309515.6937);
    }

    /**
     * @test
     */
    public function bambooBuildIsNotTriggeredWithNewRepo()
    {
        $generalClientProphecy = $this->prophesize(GeneralClient::class);
        $generalClientProphecy
            ->request('GET', 'https://raw.githubusercontent.com/TYPO3-Documentation/TYPO3CMS-Reference-CoreApi/latest/composer.json')
            ->shouldBeCalled()
            ->willReturn(new Response(200, [], file_get_contents(__DIR__ . '/Fixtures/DocsToBambooGoodRequestComposer.json')));
        TestDoubleBundle::addProphecy(GeneralClient::class, $generalClientProphecy);

        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        $bambooClientProphecy->post(Argument::cetera())->shouldNotBeCalled();
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);

        $kernel = new \App\Kernel('test', true);
        $kernel->boot();
        DatabasePrimer::prime($kernel);

        $request = require __DIR__ . '/Fixtures/DocsToBambooGoodRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }

    /**
     * @test
     */
    public function bambooBuildIsTriggered()
    {
        $generalClientProphecy = $this->prophesize(GeneralClient::class);
        $generalClientProphecy
            ->request('GET', 'https://raw.githubusercontent.com/TYPO3-Documentation/TYPO3CMS-Reference-CoreApi/latest/composer.json')
            ->shouldBeCalled()
            ->willReturn(new Response(200, [], file_get_contents(__DIR__ . '/Fixtures/DocsToBambooGoodRequestComposer.json')));
        TestDoubleBundle::addProphecy(GeneralClient::class, $generalClientProphecy);
        $slackClientProphecy = $this->prophesize(SlackClient::class);
        $slackClientProphecy->post(Argument::cetera())->shouldBeCalled()->willReturn(new Response(200, [], ''));
        TestDoubleBundle::addProphecy(SlackClient::class, $slackClientProphecy);

        $kernel = new \App\Kernel('test', true);
        $kernel->boot();
        DatabasePrimer::prime($kernel);

        $request = require __DIR__ . '/Fixtures/DocsToBambooGoodRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        $jar = $this->entityManager
            ->getRepository(DocumentationJar::class)
            ->findOneBy(['packageName' => 'johndoe/make-good']);
        $jar->setApproved(true);
        $this->entityManager->persist($jar);
        $this->entityManager->flush();

        $generalClientProphecy = $this->prophesize(GeneralClient::class);
        $generalClientProphecy
            ->request('GET', 'https://raw.githubusercontent.com/TYPO3-Documentation/TYPO3CMS-Reference-CoreApi/latest/composer.json')
            ->shouldBeCalled()
            ->willReturn(new Response(200, [], file_get_contents(__DIR__ . '/Fixtures/DocsToBambooGoodRequestComposer.json')));
        TestDoubleBundle::addProphecy(GeneralClient::class, $generalClientProphecy);
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        $bambooClientProphecy
            ->post(
                file_get_contents(__DIR__ . '/Fixtures/DocsToBambooGoodBambooPostUrl.txt'),
                require __DIR__ . '/Fixtures/DocsToBambooGoodBambooPostData.php'
            )->shouldBeCalled()
            ->willReturn(new Response());
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);

        $kernel = new \App\Kernel('test', true);
        $kernel->boot();

        $request = require __DIR__ . '/Fixtures/DocsToBambooGoodRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }

    /**
     * @test
     */
    public function bambooBuildForMultipleBranchesIsTriggered()
    {
        $generalClientProphecy = $this->prophesize(GeneralClient::class);
        $generalClientProphecy
            ->request('GET', 'https://bitbucket.org/pathfindermediagroup/eso-export-addon/raw/master/composer.json')
            ->shouldBeCalled()
            ->willReturn(new Response(200, [], file_get_contents(__DIR__ . '/Fixtures/DocsToBambooGoodMultiBranchRequestComposer.json')));
        TestDoubleBundle::addProphecy(GeneralClient::class, $generalClientProphecy);
        $generalClientProphecy
            ->request('GET', 'https://bitbucket.org/pathfindermediagroup/eso-export-addon/raw/v1.1/composer.json')
            ->shouldBeCalled()
            ->willReturn(new Response(200, [], file_get_contents(__DIR__ . '/Fixtures/DocsToBambooGoodMultiBranchRequestComposer.json')));
        TestDoubleBundle::addProphecy(GeneralClient::class, $generalClientProphecy);
        $slackClientProphecy = $this->prophesize(SlackClient::class);
        $slackClientProphecy->post(Argument::cetera())->shouldBeCalled()->willReturn(new Response(200, [], ''));
        TestDoubleBundle::addProphecy(SlackClient::class, $slackClientProphecy);

        $kernel = new \App\Kernel('test', true);
        $kernel->boot();
        DatabasePrimer::prime($kernel);

        $request = require __DIR__ . '/Fixtures/DocsToBambooGoodRequestMultiBranch.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        $jars = $this->entityManager
            ->getRepository(DocumentationJar::class)
            ->findBy(['packageName' => 'bla/yay']);
        foreach ($jars as $jar) {
            $jar->setApproved(true);
            $this->entityManager->persist($jar);
        }
        $this->entityManager->flush();

        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        $bambooClientProphecy
            ->post(
                file_get_contents(__DIR__ . '/Fixtures/DocsToBambooGoodMultiBranchBambooPostUrl.txt'),
                require __DIR__ . '/Fixtures/DocsToBambooGoodBambooPostData.php'
            )->shouldBeCalled()
            ->willReturn(new Response());
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy
            ->post(
                file_get_contents(__DIR__ . '/Fixtures/DocsToBambooGoodMultiBranchBambooPostUrlWithTag.txt'),
                require __DIR__ . '/Fixtures/DocsToBambooGoodBambooPostData.php'
            )->shouldBeCalled()
            ->willReturn(new Response());
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);

        $kernel = new \App\Kernel('test', true);
        $kernel->boot();

        $request = require __DIR__ . '/Fixtures/DocsToBambooGoodRequestMultiBranch.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }

    /**
     * @test
     */
    public function bambooBuildIsNotTriggered()
    {
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        $bambooClientProphecy->post(Argument::cetera())->shouldNotBeCalled();
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);

        $kernel = new \App\Kernel('test', true);
        $kernel->boot();
        $request = require __DIR__ . '/Fixtures/DocsToBambooBadRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }

    /**
     * @test
     */
    public function bambooBuildIsNotTriggeredDueToMissingDependency(): void
    {
        $generalClientProphecy = $this->prophesize(GeneralClient::class);
        $generalClientProphecy
            ->request('GET', 'https://raw.githubusercontent.com/TYPO3-Documentation/TYPO3CMS-Reference-CoreApi/latest/composer.json')
            ->shouldBeCalled()
            ->willReturn(new Response(200, [], file_get_contents(__DIR__ . '/Fixtures/DocsToBambooBadRequestComposerWithoutDependency.json')));
        TestDoubleBundle::addProphecy(GeneralClient::class, $generalClientProphecy);

        $kernel = new \App\Kernel('test', true);
        $kernel->boot();
        DatabasePrimer::prime($kernel);

        $request = require __DIR__ . '/Fixtures/DocsToBambooGoodRequest.php';
        $response = $kernel->handle($request);
        $this->assertSame('Dependencies are not fulfilled. See https://intercept.typo3.com for more information.', $response->getContent());
        $this->assertSame(412, $response->getStatusCode());
        $kernel->terminate($request, $response);
    }

    /**
     * cms-core can not require cms-core in its composer.json
     *
     * @test
     */
    public function bambooBuildIsTriggeredForPackageThatCanNotRequireItself(): void
    {
        $generalClientProphecy = $this->prophesize(GeneralClient::class);
        $generalClientProphecy
            ->request('GET', 'https://raw.githubusercontent.com/TYPO3-Documentation/TYPO3CMS-Reference-CoreApi/latest/composer.json')
            ->shouldBeCalled()
            ->willReturn(new Response(200, [], file_get_contents(__DIR__ . '/Fixtures/DocsToBambooGoodRequestComposerWithoutDependencyForSamePackage.json')));
        TestDoubleBundle::addProphecy(GeneralClient::class, $generalClientProphecy);
        $slackClientProphecy = $this->prophesize(SlackClient::class);
        $slackClientProphecy->post(Argument::cetera())->shouldBeCalled()->willReturn(new Response(200, [], ''));
        TestDoubleBundle::addProphecy(SlackClient::class, $slackClientProphecy);
        $kernel = new \App\Kernel('test', true);
        $kernel->boot();
        DatabasePrimer::prime($kernel);

        $request = require __DIR__ . '/Fixtures/DocsToBambooGoodRequest.php';
        $response = $kernel->handle($request);
        $this->assertSame(200, $response->getStatusCode());
        $kernel->terminate($request, $response);

        $jar = $this->entityManager
            ->getRepository(DocumentationJar::class)
            ->findOneBy(['packageName' => 'typo3/cms-core']);
        $jar->setApproved(true);
        $this->entityManager->persist($jar);
        $this->entityManager->flush();

        $generalClientProphecy = $this->prophesize(GeneralClient::class);
        $generalClientProphecy
            ->request('GET', 'https://raw.githubusercontent.com/TYPO3-Documentation/TYPO3CMS-Reference-CoreApi/latest/composer.json')
            ->shouldBeCalled()
            ->willReturn(new Response(200, [], file_get_contents(__DIR__ . '/Fixtures/DocsToBambooGoodRequestComposerWithoutDependencyForSamePackage.json')));
        TestDoubleBundle::addProphecy(GeneralClient::class, $generalClientProphecy);

        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        $bambooClientProphecy
            ->post(
                'latest/queue/CORE-DR?stage=&executeAllStages=&os_authType=basic&bamboo.variable.BUILD_INFORMATION_FILE=docs-build-information%2F1553095156937&bamboo.variable.PACKAGE=typo3%2Fcms-core&bamboo.variable.DIRECTORY=master',
                require __DIR__ . '/Fixtures/DocsToBambooGoodBambooPostData.php'
            )->shouldBeCalled()
            ->willReturn(new Response());
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);

        $kernel = new \App\Kernel('test', true);
        $kernel->boot();

        $request = require __DIR__ . '/Fixtures/DocsToBambooGoodRequest.php';
        $response = $kernel->handle($request);
        $this->assertSame(200, $response->getStatusCode());
        $kernel->terminate($request, $response);
    }

    /**
     * @test
     */
    public function bambooBuildIsNotTriggeredDueToDeletedBranch(): void
    {
        $kernel = new \App\Kernel('test', true);
        $kernel->boot();
        DatabasePrimer::prime($kernel);

        $request = require __DIR__ . '/Fixtures/DocsToBamboGithubDeletedBranchRequest.php';
        $response = $kernel->handle($request);
        $this->assertSame('The branch in this push event has been deleted.', $response->getContent());
        $this->assertSame(412, $response->getStatusCode());
        $kernel->terminate($request, $response);
    }

    /**
     * @test
     */
    public function githubPingIsHandled()
    {
        $kernel = new \App\Kernel('test', true);
        $kernel->boot();
        DatabasePrimer::prime($kernel);

        $request = require __DIR__ . '/Fixtures/DocsToBambooGithubPingRequest.php';
        $response = $kernel->handle($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('github ping', $response->getContent());
        $kernel->terminate($request, $response);
    }
}
