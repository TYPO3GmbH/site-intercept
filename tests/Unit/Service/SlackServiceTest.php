<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Unit\Service;

use App\Entity\DocumentationJar;
use App\Service\SlackService;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class SlackServiceTest extends TestCase
{
    private ?string $originalDocsLiveServer = null;

    protected function setUp(): void
    {
        $this->originalDocsLiveServer = $_ENV['DOCS_LIVE_SERVER'] ?? null;
    }

    protected function tearDown(): void
    {
        if (null === $this->originalDocsLiveServer) {
            unset($_ENV['DOCS_LIVE_SERVER']);
        } else {
            $_ENV['DOCS_LIVE_SERVER'] = $this->originalDocsLiveServer;
        }
    }

    public function testSendRepositoryDiscoveryMessageContainsExpectedPayload(): void
    {
        $capturedPayload = null;

        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient
            ->expects(self::once())
            ->method('request')
            ->with(
                'POST',
                'https://hooks.slack.com/test',
                self::callback(static function (array $options) use (&$capturedPayload): bool {
                    $capturedPayload = $options['json'] ?? null;

                    return true;
                })
            )
            ->willReturn(new Response());

        $_ENV['DOCS_LIVE_SERVER'] = 'https://docs.typo3.org/';

        $jar = new DocumentationJar();
        $jar->setVendor('acme');
        $jar->setName('my-extension');
        $jar->setPackageName('acme/my-extension');
        $jar->setTypeShort('p');
        $jar->setTargetBranchDirectory('main');

        $service = new SlackService($mockClient, 'https://hooks.slack.com/test');
        $service->sendRepositoryDiscoveryMessage($jar);

        self::assertNotNull($capturedPayload, 'Slack message payload was not captured');

        // Verify channel and identity
        self::assertSame('#typo3-documentation', $capturedPayload['channel']);
        self::assertSame('Intercept', $capturedPayload['username']);

        $attachment = $capturedPayload['attachments'][0];

        // Title should indicate awaiting approval
        self::assertStringContainsString('awaiting approval', strtolower($attachment['title']));

        // Title link must point to deployments admin
        self::assertSame('https://intercept.typo3.com/admin/docs/deployments', $attachment['title_link']);

        // Fallback must mention package and deployments URL
        self::assertStringContainsString('acme/my-extension', $attachment['fallback']);
        self::assertStringContainsString('intercept.typo3.com/admin/docs/deployments', $attachment['fallback']);

        // Text must mention the package
        self::assertStringContainsString('acme/my-extension', $attachment['text']);

        // Text must explain what happens next
        self::assertStringContainsString('maintainer', strtolower($attachment['text']));
        self::assertStringContainsString('volunteer', strtolower($attachment['text']));

        // Must contain the specific docs link for this package
        self::assertStringContainsString('docs.typo3.org/p/acme/my-extension/main/en-us', $attachment['text']);

        // Must link to deployments admin for maintainers
        self::assertStringContainsString('intercept.typo3.com/admin/docs/deployments', $attachment['text']);

        // Must link to webhook docs for extension authors
        self::assertStringContainsString('docs.typo3.org/permalink/h2document:webhook', $attachment['text']);

        // Footer must contain source link to SlackService.php on GitHub
        self::assertStringContainsString('github.com/TYPO3GmbH/site-intercept', $attachment['footer']);
        self::assertStringContainsString('SlackService.php', $attachment['footer']);

        // Must opt into mrkdwn rendering for the text field
        self::assertContains('text', $attachment['mrkdwn_in']);
    }
}
