<?php
declare(strict_types = 1);
namespace App\Tests\Functional\Service;

use App\Bundle\ClockMockBundle;
use App\Client\GeneralClient;
use App\Entity\DocumentationJar;
use App\Extractor\GithubPushEventForDocs;
use App\Service\DocumentationBuildInformationService;
use App\Tests\Functional\DatabasePrimer;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class DocumentationBuildInformationServiceTest extends KernelTestCase
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var string
     */
    private $packageName = 'foobar/baz';

    /**
     * @var string
     */
    private $branch = 'master';

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        DatabasePrimer::prime(self::$kernel);

        ClockMockBundle::withClockMock();

        $this->entityManager = self::$kernel->getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * @test
     */
    public function buildFileIsGenerated(): void
    {
        $currentTime = microtime(true);
        $currentTimeInt = ceil($currentTime * 10000);
        ClockMockBundle::register(DocumentationBuildInformationService::class);
        ClockMockBundle::withClockMock($currentTime);

        $subject = new DocumentationBuildInformationService(
            '/tmp/',
            '/tmp/',
            $this->entityManager,
            new Filesystem(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $this->getClientProphecy(
                'https://raw.githubusercontent.com/' . $this->packageName . '/' . $this->branch . '/composer.json',
                200,
                json_encode([
                    'name' => $this->packageName,
                    'type' => 'typo3-cms-framework',
                ])
            )->reveal()
        );

        $buildInformation = $subject->generateBuildInformation($this->getPushEvent());

        $this->assertSame('builds/' . $currentTimeInt, $buildInformation->getFilePath());
        $this->assertFileExists('/tmp/builds/' . $currentTimeInt);

        $expectedFileContent = ['#!/bin/bash', 'vendor=foobar', 'name=baz', 'branch=master', 'type_long=core-extension', 'type_short=c', ''];
        $this->assertSame(implode(PHP_EOL, $expectedFileContent), file_get_contents('/tmp/builds/' . $currentTimeInt));
    }

    /**
     * @test
     */
    public function renderAttemptOnForkThrowsException(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1553090750);

        $originalRepository = (new DocumentationJar())
            ->setRepositoryUrl('http://there-can-be-only-one.com/' . $this->packageName . '.git')
            ->setBranch('1.0.0')
            ->setPackageName($this->packageName);

        $this->entityManager->persist($originalRepository);
        $this->entityManager->flush();

        $subject = new DocumentationBuildInformationService(
            '/tmp/',
            '/tmp/',
            $this->entityManager,
            new Filesystem(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $this->getClientProphecy(
                'https://raw.githubusercontent.com/' . $this->packageName . '/' . $this->branch . '/composer.json',
                200,
                json_encode([
                    'name' => $this->packageName,
                    'type' => 'typo3-cms-framework',
                ])
            )->reveal()
        );

        $subject->generateBuildInformation($this->getPushEvent());
    }

    /**
     * @test
     */
    public function notExistingComposerJsonThrowsException(): void
    {
        $this->expectException(IOException::class);
        $this->expectExceptionCode(1553081065);

        $subject = new DocumentationBuildInformationService(
            '/tmp/',
            '/tmp/',
            $this->entityManager,
            new Filesystem(),
            $this->prophesize(LoggerInterface::class)->reveal(),
            $this->getClientProphecy(
                'https://raw.githubusercontent.com/' . $this->packageName . '/' . $this->branch . '/composer.json',
                404,
                ''
            )->reveal()
        );

        $subject->generateBuildInformation($this->getPushEvent());
    }

    /**
     * @test
     */
    public function fallbackToTypePackageIsLogged(): void
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy->info(Argument::any())->shouldBeCalled();

        $subject = new DocumentationBuildInformationService(
            '/tmp/',
            '/tmp/',
            $this->entityManager,
            new Filesystem(),
            $loggerProphecy->reveal(),
            $this->getClientProphecy(
                'https://raw.githubusercontent.com/' . $this->packageName . '/' . $this->branch . '/composer.json',
                200,
                json_encode([
                    'name' => $this->packageName,
                    'type' => 'something-that-triggers-fallback-to-package',
                ])
            )->reveal()
        );

        $subject->generateBuildInformation($this->getPushEvent());
    }

    /**
     * @test
     */
    public function onlyOneRecordPerRepositoryAndBranchIsCreatedOnConsecutiveCalls(): void
    {
        $iterations = 3;
        for ($i = 0; $i < $iterations; ++$i) {
            $subject = new DocumentationBuildInformationService(
                '/tmp/',
                '/tmp/',
                $this->entityManager,
                new Filesystem(),
                $this->prophesize(LoggerInterface::class)->reveal(),
                $this->getClientProphecy(
                    'https://raw.githubusercontent.com/' . $this->packageName . '/' . $this->branch . '/composer.json',
                    200,
                    json_encode([
                        'name' => $this->packageName,
                        'type' => 'typo3-cms-framework',
                    ])
                )->reveal()
            );

            $subject->generateBuildInformation($this->getPushEvent());
        }

        $this->assertCount(1, $this->entityManager->getRepository(DocumentationJar::class)->findAll());
    }

    /**
     * @return GithubPushEventForDocs
     */
    private function getPushEvent(): GithubPushEventForDocs
    {
        $payload = [
            'ref' => 'refs/tags/' . $this->branch,
            'repository' => [
                'clone_url' => 'http://myserver.com/' . $this->packageName . '.git'
            ],
        ];

        return new GithubPushEventForDocs(json_encode($payload));
    }

    /**
     * @param string $url
     * @param int $statusCode
     * @param string $responseBody
     * @return ObjectProphecy
     */
    private function getClientProphecy(string $url, int $statusCode, string $responseBody): ObjectProphecy
    {
        /** @var GeneralClient|ObjectProphecy $generalClientProphecy */
        $generalClientProphecy = $this->prophesize(GeneralClient::class);
        $generalClientProphecy->request('GET', $url)->shouldBeCalled()->willReturn(
            new Response($statusCode, [], $responseBody)
        );

        return $generalClientProphecy;
    }
}