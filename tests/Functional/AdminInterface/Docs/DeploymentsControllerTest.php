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
use App\Tests\Functional\Fixtures\AdminInterface\Docs\DeploymentsControllerTestData;
use GuzzleHttp\Client;
use Symfony\Bridge\PhpUnit\ClockMock;
use T3G\LibTestHelper\Database\DatabasePrimer;
use T3G\LibTestHelper\Request\MockRequest;

class DeploymentsControllerTest extends AbstractFunctionalWebTestCase
{
    use DatabasePrimer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->prime();
        (new DeploymentsControllerTestData())->load(
            self::$kernel->getContainer()->get('doctrine')->getManager()
        );

        ClockMock::register(DeploymentInformation::class);
        ClockMock::register(GithubService::class);
        ClockMock::withClockMock(155309515.6937);
    }

    public function testIndexRenderTableWithEntries(): void
    {
        $this->logInAsDocumentationMaintainer($this->client);
        $this->client->request('GET', '/admin/docs/deployments');
        $content = $this->client->getResponse()->getContent();
        self::assertStringContainsString('<table class="datatable-table">', $content);
        self::assertStringContainsString('typo3/docs-homepage', $content);
    }

    public function testDeleteRenderTableEntries(): void
    {
        $this->logInAsDocumentationMaintainer($this->client);

        $response = (new MockRequest($this->client))
            ->setMethod('GET')
            ->setEndPoint('/admin/docs/deployments')
            ->execute();
        self::assertStringContainsString('typo3/docs-homepage', $response->getContent());

        $githubClient = $this->createMock(Client::class);
        $githubClient
            ->expects(self::once())
            ->method('request')
            ->with('POST', '/repos/TYPO3-Documentation/t3docs-ci-deploy/dispatches', [
                'json' => [
                    'event_type' => 'delete',
                    'client_payload' => [
                        'target_branch_directory' => 'main',
                        'name' => 'docs-homepage',
                        'vendor' => 'typo3',
                        'type_short' => 'h',
                        'id' => '1a901f53acd0cce3b2e10b633af339c36b3671d5',
                    ],
                ],
            ]);

        $response = (new MockRequest($this->client))
            ->setMethod('DELETE')
            ->setEndPoint('/admin/docs/deployments/delete/1')
            ->withMock('guzzle.client.github', $githubClient)
            ->execute();
        self::assertEquals(302, $response->getStatusCode());

        $this->logInAsDocumentationMaintainer($this->client);

        $response = (new MockRequest($this->client))
            ->setMethod('GET')
            ->setEndPoint('/admin/docs/deployments')
            ->execute();
        self::assertStringContainsString('Deleting', $response->getContent());
    }

    public function testApproveEntry(): void
    {
        $this->logInAsDocumentationMaintainer($this->client);

        $response = (new MockRequest($this->client))
            ->setMethod('GET')
            ->setEndPoint('/admin/docs/deployments')
            ->execute();
        self::assertStringContainsString('Approve documentation', $response->getContent());

        $githubClient = $this->createMock(Client::class);
        $githubClient
            ->expects(self::once())
            ->method('request')
            ->with('POST', '/repos/TYPO3-Documentation/t3docs-ci-deploy/dispatches', [
                'json' => [
                    'event_type' => 'render',
                    'client_payload' => [
                        'repository_url' => 'https://github.com/georgringer/news.git',
                        'source_branch' => 'main',
                        'target_branch_directory' => 'main',
                        'name' => 'news',
                        'vendor' => 'georgringer',
                        'type_short' => 'p',
                        'id' => '6dec6ce1a86759e66b8b28a5a0a6ce0578d2febb',
                    ],
                ],
            ]);

        $response = (new MockRequest($this->client))
            ->setMethod('GET')
            ->setEndPoint('/admin/docs/deployments/approve/10')
            ->withMock('guzzle.client.github', $githubClient)
            ->execute();
        self::assertEquals(302, $response->getStatusCode());

        $this->logInAsDocumentationMaintainer($this->client);

        $response = (new MockRequest($this->client))
            ->setMethod('GET')
            ->setEndPoint('/admin/docs/deployments')
            ->execute();
        self::assertStringNotContainsString('Approve documentation', $response->getContent());
    }
}
