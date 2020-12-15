<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Functional\AdminInterface\Docs;

use App\Bundle\TestDoubleBundle;
use App\Client\BambooClient;
use App\Tests\Functional\AbstractFunctionalWebTestCase;
use App\Tests\Functional\DatabasePrimer;
use App\Tests\Functional\Fixtures\AdminInterface\Docs\RedirectControllerTestData;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Symfony\Component\DomCrawler\Crawler;

class RedirectControllerTest extends AbstractFunctionalWebTestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    private $client;

    public function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        DatabasePrimer::prime(self::$kernel);

        $this->client = static::createClient();
        (new RedirectControllerTestData())->load(
            self::$kernel->getContainer()->get('doctrine')->getManager()
        );
    }

    /**
     * @test
     */
    public function indexRenderTableWithRedirectEntries()
    {
        $this->logInAsDocumentationMaintainer($this->client);
        $this->client->request('GET', '/redirect/');
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('<table class="datatable-table">', $content);
        $this->assertStringContainsString('/p/vendor/packageOld/1.0/Foo.html', $content);
        $this->assertStringContainsString('/p/vendor/packageNew/1.0/Foo.html', $content);
    }

    /**
     * @test
     */
    public function showRenderTableWithRedirectEntries()
    {
        $this->logInAsDocumentationMaintainer($this->client);
        $this->client->request('GET', '/redirect/1');
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('<table class="datatable-table">', $content);
        $this->assertStringContainsString('/p/vendor/packageOld/1.0/Foo.html', $content);
        $this->assertStringContainsString('/p/vendor/packageNew/1.0/Foo.html', $content);
    }

    /**
     * @test
     */
    public function editRenderTableWithRedirectEntries()
    {
        $this->logInAsDocumentationMaintainer($this->client);
        $this->client->request('GET', '/redirect/1/edit');
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('/p/vendor/packageOld/1.0/Foo.html', $content);
        $this->assertStringContainsString('/p/vendor/packageNew/1.0/Foo.html', $content);
    }

    /**
     * @test
     */
    public function updateRenderTableWithRedirectEntries()
    {
        $this->createPlainBambooProphecy();
        $this->client = static::createClient();
        $this->logInAsDocumentationMaintainer($this->client);

        $crawler = $this->client->request('GET', '/redirect/1/edit');
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('/p/vendor/packageOld/1.0/Foo.html', $content);
        $this->assertStringContainsString('/p/vendor/packageNew/1.0/Foo.html', $content);
        $this->assertStringContainsString('302', $content);

        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        $bambooClientProphecy->post(Argument::cetera())->willReturn(new Response());
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);

        $form = $crawler->selectButton('docs_server_redirect_submit')->form([
            'docs_server_redirect' => [
                'source' => '/p/vendor/packageOld/1.0/Bar.html',
                'target' => '/p/vendor/packageNew/1.0/Bar.html',
                'statusCode' => 302
            ],
        ]);
        $this->client->submit($form);
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());

        $this->createPlainBambooProphecy();
        $this->client = static::createClient();
        $this->logInAsDocumentationMaintainer($this->client);

        $this->client->request('GET', '/redirect/1/edit');
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('/p/vendor/packageOld/1.0/Bar.html', $content);
        $this->assertStringContainsString('/p/vendor/packageNew/1.0/Bar.html', $content);
        $this->assertStringContainsString('302', $content);
    }

    /**
     * @test
     */
    public function newRenderTableWithRedirectEntries()
    {
        $this->createPlainBambooProphecy();
        $this->client = static::createClient();
        $this->logInAsDocumentationMaintainer($this->client);

        $crawler = $this->client->request('GET', '/redirect/new');
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        $bambooClientProphecy->post(Argument::cetera())->willReturn(new Response());
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);

        $form = $crawler->selectButton('docs_server_redirect_submit')->form([
            'docs_server_redirect' => [
                'source' => '/p/vendor/packageOld/4.0/Bar.html',
                'target' => '/p/vendor/packageNew/4.0/Bar.html',
                'statusCode' => 303
            ],
        ]);
        $this->client->submit($form);
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());

        $this->createPlainBambooProphecy();
        $this->client = static::createClient();
        $this->logInAsDocumentationMaintainer($this->client);

        $this->client->request('GET', '/redirect/');
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('/p/vendor/packageOld/4.0/Bar.html', $content);
        $this->assertStringContainsString('/p/vendor/packageNew/4.0/Bar.html', $content);
    }

    /**
     * @test
     */
    public function deleteRenderTableWithRedirectEntries()
    {
        $this->createPlainBambooProphecy();
        $this->client = static::createClient();
        $this->logInAsDocumentationMaintainer($this->client);

        $this->client->request('GET', '/redirect/');
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('/p/vendor/packageOld/1.0/Foo.html', $content);
        $this->assertStringContainsString('/p/vendor/packageNew/1.0/Foo.html', $content);

        $crawler = $this->client->request('GET', '/redirect/1/edit');
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        $bambooClientProphecy->post(Argument::cetera())->willReturn(new Response());
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);

        $form = $crawler->selectButton('redirectformdelete')->form();
        $this->client->submit($form);
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());

        $this->createPlainBambooProphecy();
        $this->client = static::createClient();
        $this->logInAsDocumentationMaintainer($this->client);

        $this->client->request('GET', '/redirect/');

        $content = $this->client->getResponse()->getContent();
        $this->assertStringNotContainsString('/p/vendor/packageOld/1.0/Foo.html', $content);
        $this->assertStringNotContainsString('/p/vendor/packageNew/1.0/Foo.html', $content);
        $this->assertStringContainsString('no records found', $content);
    }

    private function createPlainBambooProphecy()
    {
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);
        $bambooClientProphecy->get('latest/agent/remote?os_authType=basic', Argument::cetera())->willReturn(
            new Response(200, [], json_encode([]))
        );
        $bambooClientProphecy->get('latest/queue?os_authType=basic', Argument::cetera())->willReturn(
            new Response(200, [], json_encode([]))
        );
    }

    /**
     * @test
     * @dataProvider invalidRedirectStrings
     */
    public function invalidSourceInputTriggersValidationError(string $input)
    {
        $this->client = static::createClient();
        $this->logInAsDocumentationMaintainer($this->client);

        $crawler = $this->client->request('GET', '/redirect/new');
        $form = $crawler->selectButton('docs_server_redirect_submit')->form([
            'docs_server_redirect' => [
                'source' => $input,
                'target' => '/p/vendor/packageNew/4.0/Bar.html',
                'statusCode' => 303
            ],
        ]);
        $this->client->submit($form);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $content = $response->getContent();

        $responseCrawler = new Crawler('');
        $responseCrawler->addHtmlContent($content);
        $sourceLabel = $responseCrawler->filter('#docs_server_redirect div label')->text();
        $this->assertStringContainsString('Source', $sourceLabel);
    }

    /**
     * @test
     * @dataProvider invalidRedirectStrings
     * @param string $input
     */
    public function invalidTargetInputTriggersValidationError(string $input)
    {
        $this->client = static::createClient();
        $this->logInAsDocumentationMaintainer($this->client);

        $crawler = $this->client->request('GET', '/redirect/new');
        $form = $crawler->selectButton('docs_server_redirect_submit')->form([
            'docs_server_redirect' => [
                'source' => '/p/vendor/packageOld/1.0/Foo.html',
                'target' => $input,
                'statusCode' => 303
            ],
        ]);

        $this->client->submit($form);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $content = $response->getContent();

        $responseCrawler = new Crawler('');
        $responseCrawler->addHtmlContent($content);
        $targetLabel = $responseCrawler->filter('#docs_server_redirect')->children()->getNode(1)->textContent;
        $this->assertStringContainsString('Target', $targetLabel);
    }

    /**
     * @test
     * @dataProvider validRedirectStrings
     * @param string $input
     */
    public function validTargetInputTriggersFormSubmit(string $input)
    {
        $this->createPlainBambooProphecy();
        $this->client = static::createClient();
        $this->logInAsDocumentationMaintainer($this->client);

        $crawler = $this->client->request('GET', '/redirect/new');
        $form = $crawler->selectButton('docs_server_redirect_submit')->form([
            'docs_server_redirect' => [
                'source' => $input,
                'target' => $input,
                'statusCode' => 303
            ],
        ]);
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        $bambooClientProphecy->post(Argument::cetera())->willReturn(new Response());
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);

        $this->client->submit($form);
        $response = $this->client->getResponse();
        $this->assertEquals(302, $response->getStatusCode());
        $content = $response->getContent();
        $this->assertStringContainsString('Redirecting to <a href="/redirect/">/redirect/</a>.', $content);
    }

    public function validRedirectStrings(): array
    {
        return [
            'package' => [
                '/p/vendor/packageOld/2.0/Foo.html',
            ],
            'manual' => [
                '/m/vendor/packageOld/2.0/Foo.html',
            ],
            'system extension' => [
                '/c/vendor/packageOld/2.0/Foo.html',
            ],
            'home' => [
                '/h/vendor/packageOld/2.0/Foo.html',
            ],
            'third party' => [
                '/other/vendor/packageOld/2.0/Foo.html',
            ],
        ];
    }

    public function invalidRedirectStrings(): array
    {
        return [
            'something random' => [
                'invalid-target/String',
            ],
            'package without vendor' => [
                '/p/packageOld/2.0/Foo.html',
            ],
        ];
    }
}
