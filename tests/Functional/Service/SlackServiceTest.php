<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Functional\Service;

use App\Entity\DocumentationJar;
use App\Service\SlackService;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SlackServiceTest extends KernelTestCase
{
    public function testSendRepositoryDiscoveryMessageContainsExpectedPayload(): void
    {
        $capturedPayload = null;

        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient
            ->expects(self::once())
            ->method('request')
            ->with(
                'POST',
                'http://localhost/slack',
                self::callback(static function (array $options) use (&$capturedPayload): bool {
                    $capturedPayload = $options['json'] ?? null;

                    return true;
                })
            )
            ->willReturn(new Response());

        self::getContainer()->set('guzzle.client.slack', $mockClient);

        $jar = new DocumentationJar();
        $jar->setVendor('acme');
        $jar->setName('my-extension');
        $jar->setPackageName('acme/my-extension');
        $jar->setTypeShort('p');
        $jar->setTargetBranchDirectory('main');

        $service = self::getContainer()->get(SlackService::class);
        $service->sendRepositoryDiscoveryMessage($jar);

        self::assertNotNull($capturedPayload, 'Slack message payload was not captured');

        // Verify channel and identity
        self::assertSame('#typo3-documentation', $capturedPayload['channel']);
        self::assertSame('Intercept', $capturedPayload['username']);

        $attachment = $capturedPayload['attachments'][0];

        // Title should indicate awaiting approval
        self::assertStringContainsString('awaiting approval', strtolower($attachment['title']));

        // Title link must point to deployments admin
        self::assertSame('https://localhost/admin/docs/deployments', $attachment['title_link']);

        // Fallback must mention package and deployments URL
        self::assertStringContainsString('acme/my-extension', $attachment['fallback']);
        self::assertStringContainsString('localhost/admin/docs/deployments', $attachment['fallback']);

        // Text must mention the package
        self::assertStringContainsString('acme/my-extension', $attachment['text']);

        // Text must explain what happens next
        self::assertStringContainsString('maintainer', strtolower($attachment['text']));
        self::assertStringContainsString('volunteer', strtolower($attachment['text']));

        // Must contain the specific docs link for this package
        self::assertStringContainsString('localhost/docs/p/acme/my-extension/main/en-us', $attachment['text']);

        // Must link to deployments admin for maintainers
        self::assertStringContainsString('localhost/admin/docs/deployments', $attachment['text']);

        // Must link to webhook docs for extension authors
        self::assertStringContainsString('localhost/docs/permalink/h2document:webhook', $attachment['text']);

        // Footer must contain source link to SlackService.php on GitHub
        self::assertStringContainsString('github.com/TYPO3GmbH/site-intercept', $attachment['footer']);
        self::assertStringContainsString('SlackService.php', $attachment['footer']);

        // Must opt into mrkdwn rendering for the text field
        self::assertContains('text', $attachment['mrkdwn_in']);
    }
}
