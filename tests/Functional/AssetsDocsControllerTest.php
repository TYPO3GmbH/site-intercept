<?php
declare(strict_types = 1);
namespace App\Tests\Functional;

use App\Service\DocsAssetsService;
use App\Tests\Functional\Fixtures\AssetsDocsControllerTestData;
use Symfony\Bundle\FrameworkBundle\Client;

class AssetsDocsControllerTest extends AbstractFunctionalWebTestCase
{
    /**
     * @var Client
     */
    private $client;

    public function setUp()
    {
        parent::setUp();
        self::bootKernel();
        DatabasePrimer::prime(self::$kernel);

        $this->client = static::createClient();
        (new AssetsDocsControllerTestData())->load(
            self::$kernel->getContainer()->get('doctrine')->getManager()
        );
    }

    /**
     * @test
     */
    public function manualsJsonContainsCommunityExtensionsOnly(): void
    {
        $this->client->request('GET', '/assets/docs/manuals.json');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $content = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('blog', $content);
        $this->assertArrayNotHasKey('felogin', $content);
        $this->assertArrayNotHasKey('docs_that_is_no_extension', $content);
    }

    /**
     * @test
     */
    public function manualsJsonContainsMultipleVersionsWithoutDraft(): void
    {
        $this->client->request('GET', '/assets/docs/manuals.json');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $content = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('master', $content['blog']['docs']);
        $this->assertArrayHasKey('8.7.4', $content['blog']['docs']);
        $this->assertArrayHasKey('8.7', $content['blog']['docs']);
        $this->assertArrayNotHasKey('draft', $content['blog']['docs']);
    }

    /**
     * @test
     */
    public function extensionsJavaScriptContainsCoreAndCommunityExtensionsOnly(): void
    {
        $this->client->request('GET', '/assets/docs/extensions.js');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $content = $this->client->getResponse()->getContent();

        $this->assertStringContainsString('"key":"blog"', $content);
        $this->assertStringContainsString('"key":"felogin"', $content);
        $this->assertStringContainsString('"key":"news"', $content);
        $this->assertStringNotContainsString('"key":"docs_that_is_no_extension"', $content);
    }

    /**
     * @test
     */
    public function extensionsJavaScriptContainsMultipleVersionsWithoutDraft(): void
    {
        $this->client->request('GET', '/assets/docs/extensions.js');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $content = $this->client->getResponse()->getContent();

        $this->assertStringContainsString('"key":"blog","latest":"8.7","versions":["master","8.7"]', $content);
        $this->assertStringContainsString('"key":"felogin","latest":"9.5","versions":["master","9.5","8.7"]', $content);
        $this->assertStringContainsString('"key":"news","latest":"master","versions":["master"]', $content);
    }
}
