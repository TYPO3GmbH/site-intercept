<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Unit\Service;

use App\Entity\KnownRepositoryDomain;
use App\Enum\RepositoryDomainStatus;
use App\Exception\InvalidComposerJsonUrlException;
use App\Repository\DocumentationJarRepository;
use App\Repository\KnownRepositoryDomainRepository;
use App\Service\DocumentationBuildInformationService;
use App\Service\MailService;
use App\Service\SlackService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class ComposerJsonUrlShapeTest extends TestCase
{
    public static function urlsBuiltForTheSupportedServicesDataProvider(): \Iterator
    {
        yield 'bitbucket cloud' => ['https://allowed.example/acme/ext/raw/main/composer.json'];
        yield 'bitbucket server' => ['https://allowed.example/projects/EXT/repos/ext/raw/composer.json?at=refs%2Fheads%2Fmain'];
        yield 'gitlab' => ['https://allowed.example/acme/ext/raw/main/composer.json'];
        yield 'github' => ['https://allowed.example/acme/ext/main/composer.json'];
        yield 'forgejo' => ['https://allowed.example/acme/ext/raw/branch/main/composer.json'];
    }

    #[DataProvider('urlsBuiltForTheSupportedServicesDataProvider')]
    public function testUrlsTheServicesActuallyProduceArePassed(string $url): void
    {
        $composerJson = $this->buildSubject()->fetchRemoteComposerJson($url);

        $this->assertSame('acme/ext', $composerJson['name']);
    }

    public static function manipulatedUrlsDataProvider(): \Iterator
    {
        // A '#' turns the expected '/…/composer.json' suffix into a fragment,
        // which is dropped before the request is sent
        yield 'fragment cuts off the expected path' => ['https://allowed.example/internal/admin#/raw/branch/main/composer.json'];
        // A '?' in the base url pushes the expected suffix into the query string
        yield 'query swallows the expected path' => ['https://allowed.example/api/v4/user?a=/raw/branch/main/composer.json'];
        yield 'path traversal out of the repository' => ['https://allowed.example/acme/ext/raw/branch/../../../../etc/passwd'];
        yield 'no composer.json at all' => ['https://allowed.example/acme/ext/raw/branch/main/'];
    }

    #[DataProvider('manipulatedUrlsDataProvider')]
    public function testUrlsNotPointingAtAComposerJsonAreRejected(string $url): void
    {
        $this->expectException(InvalidComposerJsonUrlException::class);

        $this->buildSubject()->fetchRemoteComposerJson($url);
    }

    private function buildSubject(): DocumentationBuildInformationService
    {
        $knownDomain = (new KnownRepositoryDomain())->setDomain('allowed.example')->setStatus(RepositoryDomainStatus::ALLOWED);
        $knownRepositoryDomainRepository = $this->createMock(KnownRepositoryDomainRepository::class);
        $knownRepositoryDomainRepository->method('findOneBy')->willReturn($knownDomain);

        return new DocumentationBuildInformationService(
            '/tmp',
            'sub',
            $this->createMock(DocumentationJarRepository::class),
            $knownRepositoryDomainRepository,
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(Filesystem::class),
            new Client(['handler' => HandlerStack::create(new MockHandler([new Response(200, [], '{"name": "acme/ext"}')]))]),
            $this->createMock(SlackService::class),
            $this->createMock(MailService::class),
        );
    }
}
