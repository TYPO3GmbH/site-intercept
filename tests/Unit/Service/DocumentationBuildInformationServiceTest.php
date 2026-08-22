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
use App\Exception\ComposerJsonNotFoundException;
use App\Exception\UnknownComposerJsonUrlException;
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
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class DocumentationBuildInformationServiceTest extends TestCase
{
    /**
     * A redirect must not be able to take the request to a domain the allowlist
     * would have rejected, so every hop is checked again.
     */
    public function testRedirectToAnUnknownDomainIsRejected(): void
    {
        $subject = $this->buildSubject(
            allowedDomain: 'allowed.example',
            responses: [
                new Response(302, ['Location' => 'https://evil.example/composer.json']),
                new Response(200, [], '{"name": "should/never-be-reached"}'),
            ]
        );

        $this->expectException(UnknownComposerJsonUrlException::class);

        $subject->fetchRemoteComposerJson('https://allowed.example/acme/ext/raw/branch/main/composer.json');
    }

    public function testRedirectStayingOnAnAllowedDomainIsFollowed(): void
    {
        $subject = $this->buildSubject(
            allowedDomain: 'allowed.example',
            responses: [
                new Response(302, ['Location' => 'https://allowed.example/elsewhere/composer.json']),
                new Response(200, [], '{"name": "acme/ext"}'),
            ]
        );

        $composerJson = $subject->fetchRemoteComposerJson('https://allowed.example/acme/ext/raw/branch/main/composer.json');

        $this->assertSame('acme/ext', $composerJson['name']);
    }

    public function testResponseWithoutARedirectIsReturned(): void
    {
        $subject = $this->buildSubject(
            allowedDomain: 'allowed.example',
            responses: [new Response(200, [], '{"name": "acme/ext"}')]
        );

        $composerJson = $subject->fetchRemoteComposerJson('https://allowed.example/acme/ext/raw/branch/main/composer.json');

        $this->assertSame('acme/ext', $composerJson['name']);
    }

    public function testNonSuccessfulResponseIsReportedAsNotFound(): void
    {
        $subject = $this->buildSubject(
            allowedDomain: 'allowed.example',
            responses: [new Response(404)]
        );

        $this->expectException(ComposerJsonNotFoundException::class);

        $subject->fetchRemoteComposerJson('https://allowed.example/acme/ext/raw/branch/main/composer.json');
    }

    /**
     * @param Response[] $responses
     */
    private function buildSubject(string $allowedDomain, array $responses): DocumentationBuildInformationService
    {
        $knownDomain = (new KnownRepositoryDomain())->setDomain($allowedDomain)->setStatus(RepositoryDomainStatus::ALLOWED);

        $knownRepositoryDomainRepository = $this->createMock(KnownRepositoryDomainRepository::class);
        $knownRepositoryDomainRepository->method('findOneBy')->willReturnCallback(
            static fn (array $criteria): ?KnownRepositoryDomain => ($criteria['domain'] ?? null) === $allowedDomain ? $knownDomain : null
        );

        return new DocumentationBuildInformationService(
            '/tmp',
            'sub',
            $this->createMock(DocumentationJarRepository::class),
            $knownRepositoryDomainRepository,
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(Filesystem::class),
            new Client(['handler' => HandlerStack::create(new MockHandler($responses))]),
            $this->createMock(SlackService::class),
            $this->createMock(MailService::class),
        );
    }
}
