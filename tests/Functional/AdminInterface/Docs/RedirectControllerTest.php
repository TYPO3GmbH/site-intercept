<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Functional\AdminInterface\Docs;

use App\Extractor\DeploymentInformation;
use App\Service\GithubService;
use App\Tests\Functional\AbstractFunctionalWebTestCase;
use App\Tests\Functional\Fixtures\AdminInterface\Docs\RedirectControllerTestData;
use GuzzleHttp\Client;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bridge\PhpUnit\ClockMock;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use T3G\LibTestHelper\Database\DatabasePrimer;
use T3G\LibTestHelper\Request\MockRequest;

class RedirectControllerTest extends AbstractFunctionalWebTestCase
{
    use DatabasePrimer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->prime();
        (new RedirectControllerTestData())->load(
            self::$kernel->getContainer()->get('doctrine')->getManager()
        );

        ClockMock::register(DeploymentInformation::class);
        ClockMock::register(GithubService::class);
        ClockMock::withClockMock(155309515.6937);
    }

    public function testIndexRenderTableWithRedirectEntries(): void
    {
        $this->logInAsDocumentationMaintainer($this->client);
        $this->client->request('GET', '/redirect/');
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('<table class="datatable-table">', $content);
        $this->assertStringContainsString('/p/vendor/packageOld/1.0/Foo.html', $content);
        $this->assertStringContainsString('/p/vendor/packageNew/1.0/Foo.html', $content);
    }

    public function testShowRenderTableWithRedirectEntries(): void
    {
        $this->logInAsDocumentationMaintainer($this->client);
        $this->client->request('GET', '/redirect/1');
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('<table class="datatable-table">', $content);
        $this->assertStringContainsString('/p/vendor/packageOld/1.0/Foo.html', $content);
        $this->assertStringContainsString('/p/vendor/packageNew/1.0/Foo.html', $content);
    }

    public function testEditRenderTableWithRedirectEntries(): void
    {
        $this->logInAsDocumentationMaintainer($this->client);
        $this->client->request('GET', '/redirect/1/edit');
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('/p/vendor/packageOld/1.0/Foo.html', $content);
        $this->assertStringContainsString('/p/vendor/packageNew/1.0/Foo.html', $content);
    }

    public function testUpdateRenderTableWithRedirectEntries(): void
    {
        $this->logInAsDocumentationMaintainer($this->client);

        $response = (new MockRequest($this->client))
            ->setMethod('GET')
            ->setEndPoint('/redirect/1/edit')
            ->execute();
        $content = $response->getContent();
        $this->assertStringContainsString('/p/vendor/packageOld/1.0/Foo.html', $content);
        $this->assertStringContainsString('/p/vendor/packageNew/1.0/Foo.html', $content);
        $this->assertStringContainsString('302', $content);

        $this->rebootState();

        $this->logInAsDocumentationMaintainer($this->client);

        $githubClient = $this->createMock(Client::class);
        $githubClient
            ->expects($this->once())
            ->method('request')
            ->with('POST', '/repos/TYPO3-Documentation/t3docs-ci-deploy/dispatches', [
                'json' => [
                    'event_type' => 'redirect',
                    'client_payload' => [
                        'id' => 'fc9a9c60fe8aff9e85190aebe1c42361c373b8c6',
                    ],
                ],
            ]);

        $response = (new MockRequest($this->client))
            ->setMethod('POST')
            ->setEndPoint('/redirect/1/edit')
            ->setBody([
                'docs_server_redirect' => [
                    'source' => '/p/vendor/packageOld/1.0/Bar.html',
                    'target' => '/p/vendor/packageNew/1.0/Bar.html',
                    'statusCode' => 302,
                ],
            ])
            ->withMock('guzzle.client.github', $githubClient)
            ->execute();
        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());

        $this->logInAsDocumentationMaintainer($this->client);

        $this->client->request('GET', '/redirect/1/edit');
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('/p/vendor/packageOld/1.0/Bar.html', $content);
        $this->assertStringContainsString('/p/vendor/packageNew/1.0/Bar.html', $content);
        $this->assertStringContainsString('302', $content);
    }

    public function testNewRenderTableWithRedirectEntries(): void
    {
        $this->logInAsDocumentationMaintainer($this->client);

        $githubClient = $this->createMock(Client::class);
        $githubClient
            ->expects($this->once())
            ->method('request')
            ->with('POST', '/repos/TYPO3-Documentation/t3docs-ci-deploy/dispatches', [
                'json' => [
                    'event_type' => 'redirect',
                    'client_payload' => [
                        'id' => 'fc9a9c60fe8aff9e85190aebe1c42361c373b8c6',
                    ],
                ],
            ]);

        $response = (new MockRequest($this->client))
            ->setMethod('POST')
            ->setEndPoint('/redirect/new')
            ->setBody([
                'docs_server_redirect' => [
                    'source' => '/p/vendor/packageOld/4.0/Bar.html',
                    'target' => '/p/vendor/packageNew/4.0/Bar.html',
                    'statusCode' => 303,
                ],
            ])
            ->withMock('guzzle.client.github', $githubClient)
            ->execute();
        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());

        $this->logInAsDocumentationMaintainer($this->client);

        $this->client->request('GET', '/redirect/');
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('/p/vendor/packageOld/4.0/Bar.html', $content);
        $this->assertStringContainsString('/p/vendor/packageNew/4.0/Bar.html', $content);
    }

    public function testDeleteRenderTableWithRedirectEntries(): void
    {
        $this->logInAsDocumentationMaintainer($this->client);

        $this->client->request('GET', '/redirect/');
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('/p/vendor/packageOld/1.0/Foo.html', $content);
        $this->assertStringContainsString('/p/vendor/packageNew/1.0/Foo.html', $content);

        $this->rebootState();

        $this->logInAsDocumentationMaintainer($this->client);

        $githubClient = $this->createMock(Client::class);
        $githubClient
            ->expects($this->once())
            ->method('request')
            ->with('POST', '/repos/TYPO3-Documentation/t3docs-ci-deploy/dispatches', [
                'json' => [
                    'event_type' => 'redirect',
                    'client_payload' => [
                        'id' => 'fc9a9c60fe8aff9e85190aebe1c42361c373b8c6',
                    ],
                ],
            ]);

        $response = (new MockRequest($this->client))
            ->setMethod('DELETE')
            ->setEndPoint('/redirect/1')
            ->setBody([
                'delete_redirect' => [
                    'delete' => '',
                ],
            ])
            ->withMock('guzzle.client.github', $githubClient)
            ->execute();
        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());

        $this->logInAsDocumentationMaintainer($this->client);

        $this->client->request('GET', '/redirect/');

        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('no records found', $content);
    }

    #[DataProvider('invalidRedirectStringsDataProvider')]
    public function testInvalidSourceInputTriggersValidationError(string $input): void
    {
        $this->logInAsDocumentationMaintainer($this->client);

        $crawler = $this->client->request('GET', '/redirect/new');
        $form = $crawler->selectButton('docs_server_redirect_submit')->form([
            'docs_server_redirect' => [
                'source' => $input,
                'target' => '/p/vendor/packageNew/4.0/Bar.html',
                'statusCode' => 303,
            ],
        ]);
        $this->client->submit($form);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $content = $response->getContent();

        $responseCrawler = new Crawler('');
        $responseCrawler->addHtmlContent($content);
        $sourceLabel = $responseCrawler->filter('#docs_server_redirect div label')->text();
        $this->assertStringContainsString('Source', $sourceLabel);
    }

    #[DataProvider('invalidRedirectStringsDataProvider')]
    public function testInvalidTargetInputTriggersValidationError(string $input): void
    {
        $this->logInAsDocumentationMaintainer($this->client);

        $crawler = $this->client->request('GET', '/redirect/new');
        $form = $crawler->selectButton('docs_server_redirect_submit')->form([
            'docs_server_redirect' => [
                'source' => '/p/vendor/packageOld/1.0/Foo.html',
                'target' => $input,
                'statusCode' => 303,
            ],
        ]);

        $this->client->submit($form);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $content = $response->getContent();

        $responseCrawler = new Crawler('');
        $responseCrawler->addHtmlContent($content);
        $targetLabel = $responseCrawler->filter('#docs_server_redirect')->children()->getNode(1)->textContent;
        $this->assertStringContainsString('Target', $targetLabel);
    }

    #[DataProvider('validRedirectStringsDataProvider')]
    public function testValidTargetInputTriggersFormSubmit(string $input): void
    {
        $this->logInAsDocumentationMaintainer($this->client);

        $githubClient = $this->createMock(Client::class);
        $githubClient
            ->expects($this->once())
            ->method('request')
            ->with('POST', '/repos/TYPO3-Documentation/t3docs-ci-deploy/dispatches', [
                'json' => [
                    'event_type' => 'redirect',
                    'client_payload' => [
                        'id' => 'fc9a9c60fe8aff9e85190aebe1c42361c373b8c6',
                    ],
                ],
            ]);

        $response = (new MockRequest($this->client))
            ->setMethod('POST')
            ->setEndPoint('/redirect/new')
            ->setBody([
                'docs_server_redirect' => [
                    'source' => $input,
                    'target' => $input,
                    'statusCode' => 303,
                ],
            ])
            ->withMock('guzzle.client.github', $githubClient)
            ->execute();
        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertStringContainsString('Redirecting to <a href="/redirect/">/redirect/</a>.', $response->getContent());
    }

    public static function validRedirectStringsDataProvider(): \Iterator
    {
        yield 'package' => [
            '/p/vendor/packageOld/2.0/Foo.html',
        ];
        yield 'manual' => [
            '/m/vendor/packageOld/2.0/Foo.html',
        ];
        yield 'system extension' => [
            '/c/vendor/packageOld/2.0/Foo.html',
        ];
        yield 'home' => [
            '/h/vendor/packageOld/2.0/Foo.html',
        ];
        yield 'third party' => [
            '/other/vendor/packageOld/2.0/Foo.html',
        ];
    }

    public static function invalidRedirectStringsDataProvider(): \Iterator
    {
        yield 'something random' => [
            'invalid-target/String',
        ];
        yield 'package without vendor' => [
            '/p/packageOld/2.0/Foo.html',
        ];
    }
}
