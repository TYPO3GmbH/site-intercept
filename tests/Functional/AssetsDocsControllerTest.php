<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Functional;

use App\Tests\Functional\Fixtures\AssetsDocsControllerTestData;
use Symfony\Component\HttpFoundation\Response;
use T3G\LibTestHelper\Database\DatabasePrimer;

class AssetsDocsControllerTest extends AbstractFunctionalWebTestCase
{
    use DatabasePrimer;

    public function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->prime();

        (new AssetsDocsControllerTestData())->load(
            self::$kernel->getContainer()->get('doctrine')->getManager()
        );
    }

    public function testManualsJsonContainsCommunityExtensionsOnly(): void
    {
        $this->client->request('GET', '/assets/docs/manuals.json');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $content = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('blog', $content);
        $this->assertArrayNotHasKey('adminpanel', $content);
        $this->assertArrayNotHasKey('docs_that_is_no_extension', $content);
    }

    public function testManualsJsonContainsMultipleVersionsWithoutDraft(): void
    {
        $this->client->request('GET', '/assets/docs/manuals.json');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $content = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertArrayHasKey('main', $content['blog']['docs']);
        $this->assertArrayHasKey('8.7.4', $content['blog']['docs']);
        $this->assertArrayHasKey('8.7', $content['blog']['docs']);
        $this->assertArrayNotHasKey('draft', $content['blog']['docs']);
    }

    public function testExtensionsJavaScriptContainsCoreAndCommunityExtensionsOnly(): void
    {
        $this->client->request('GET', '/assets/docs/extensions.js');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $content = $this->client->getResponse()->getContent();

        $this->assertStringContainsString('"key":"t3g\/blog"', $content);
        $this->assertStringContainsString('"key":"typo3\/cms-felogin"', $content);
        $this->assertStringContainsString('"key":"georgringer\/news"', $content);
        $this->assertStringNotContainsString('"key":"docs_that_is_no_extension"', $content);
    }

    public function testExtensionsJavaScriptContainsMultipleVersionsWithoutDraft(): void
    {
        $this->client->request('GET', '/assets/docs/extensions.js');
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $content = $this->client->getResponse()->getContent();

        $this->assertStringContainsString('"key":"t3g\/blog","extensionKey":"blog","latest":"9.1","versions":{"main":"\/p\/t3g\/blog\/main\/en-us","9.1":"\/typo3cms\/extensions\/blog\/9.1.1","9.0":"\/typo3cms\/extensions\/blog\/9.0.0","8.7":"\/p\/t3g\/blog\/8.7\/en-us"', $content);
    }
}
