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
use Symfony\Component\HttpFoundation\Response;
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
        $this->assertStringContainsString('<table class="datatable-table">', $content);
        $this->assertStringContainsString('typo3/docs-homepage', $content);
    }

    public function testDeleteRenderTableEntries(): void
    {
        $this->logInAsDocumentationMaintainer($this->client);

        $response = (new MockRequest($this->client))
            ->setMethod('GET')
            ->setEndPoint('/admin/docs/deployments')
            ->execute();
        $this->assertStringContainsString('typo3/docs-homepage', $response->getContent());

        $githubClient = $this->createMock(Client::class);
        $githubClient
            ->expects($this->once())
            ->method('request')
            ->with('POST', '/repos/TYPO3-Documentation/t3docs-ci-deploy/dispatches', self::anything());

        $response = (new MockRequest($this->client))
            ->setMethod('DELETE')
            ->setEndPoint('/admin/docs/deployments/delete/1')
            ->setBody([
                'delete_deployment' => [
                    'delete' => '',
                ],
            ])
            ->withMock('guzzle.client.github', $githubClient)
            ->execute();
        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());

        $this->logInAsDocumentationMaintainer($this->client);

        $response = (new MockRequest($this->client))
            ->setMethod('GET')
            ->setEndPoint('/admin/docs/deployments')
            ->execute();
        $this->assertStringContainsString('Deleting', $response->getContent());
    }

    public function testApproveEntry(): void
    {
        $this->logInAsDocumentationMaintainer($this->client);

        $response = (new MockRequest($this->client))
            ->setMethod('GET')
            ->setEndPoint('/admin/docs/deployments')
            ->execute();
        $this->assertStringContainsString('Approve documentation', $response->getContent());

        $githubClient = $this->createMock(Client::class);
        $githubClient
            ->expects($this->once())
            ->method('request')
            ->with('POST', '/repos/TYPO3-Documentation/t3docs-ci-deploy/dispatches', self::anything());

        $response = (new MockRequest($this->client))
            ->setMethod('GET')
            ->setEndPoint('/admin/docs/deployments/approve/10')
            ->withMock('guzzle.client.github', $githubClient)
            ->execute();
        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());

        $this->logInAsDocumentationMaintainer($this->client);

        $response = (new MockRequest($this->client))
            ->setMethod('GET')
            ->setEndPoint('/admin/docs/deployments')
            ->execute();
        $this->assertStringNotContainsString('Approve documentation', $response->getContent());
    }
}
