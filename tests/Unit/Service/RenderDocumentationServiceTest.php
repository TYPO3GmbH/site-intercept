<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Unit\Service;

use App\Entity\DocumentationQuarantine;
use App\Enum\DocumentationRenderingTrigger;
use App\Exception\DocumentationRenderingRequestDeclinedException;
use App\Exception\UnknownComposerJsonUrlException;
use App\Extractor\PushEvent;
use App\Repository\RepositoryBlacklistEntryRepository;
use App\Service\DocumentationBuildInformationService;
use App\Service\DocumentationQuarantineService;
use App\Service\GithubService;
use App\Service\HistoryService;
use App\Service\MailService;
use App\Service\RenderDocumentationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Bundle\SecurityBundle\Security;

class RenderDocumentationServiceTest extends TestCase
{
    /**
     * The quarantined domain is what an admin later allowlists, so it has to be
     * the host the request was blocked on. That is the host of the composer.json
     * url, which for Github is never the host of the clone url.
     */
    public function testTheBlockedDomainIsHandedToTheQuarantine(): void
    {
        $pushEvent = new PushEvent(
            'https://github.com/acme/coolextension.git',
            'main',
            'https://raw.githubusercontent.com/acme/coolextension/main/composer.json',
            '{}'
        );

        $buildInformationService = $this->createMock(DocumentationBuildInformationService::class);
        $buildInformationService->method('fetchRemoteComposerJson')->willThrowException(
            new UnknownComposerJsonUrlException('', 1782290340, null, $pushEvent->getUrlToComposerFile(), 'raw.githubusercontent.com')
        );

        $lastHitDomain = null;
        $buildInformationService->method('updateLastHit')->willReturnCallback(
            static function (string $domain) use (&$lastHitDomain): void {
                $lastHitDomain = $domain;
            }
        );

        $quarantinedDomain = null;
        $quarantineService = $this->createMock(DocumentationQuarantineService::class);
        $quarantineService->method('isQuarantined')->willReturn(false);
        $quarantineService->method('quarantine')->willReturnCallback(
            static function (PushEvent $event, string $domain) use (&$quarantinedDomain): DocumentationQuarantine {
                $quarantinedDomain = $domain;

                return new DocumentationQuarantine();
            }
        );

        $subject = new RenderDocumentationService(
            $buildInformationService,
            $this->createMock(GithubService::class),
            new HistoryService($this->createMock(EntityManagerInterface::class)),
            new NullLogger(),
            $quarantineService,
            $this->createMock(RepositoryBlacklistEntryRepository::class),
            $this->createMock(MailService::class),
            $this->createMock(Security::class),
        );

        try {
            $subject->requestDocumentationRendering($pushEvent, DocumentationRenderingTrigger::API);
            $this->fail('An unknown domain has to decline the rendering request.');
        } catch (DocumentationRenderingRequestDeclinedException) {
            // expected, the assertions below are what this test is about
        }

        $this->assertSame('raw.githubusercontent.com', $quarantinedDomain, 'The quarantine has to record the host the request was blocked on.');
        $this->assertSame('raw.githubusercontent.com', $lastHitDomain, 'The last hit belongs to the domain row the check looks up.');
    }
}
