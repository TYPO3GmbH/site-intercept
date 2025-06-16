<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Functional;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use T3G\LibTestHelper\Request\AssertRequestTrait;
use T3G\LibTestHelper\Request\MockRequest;
use T3G\LibTestHelper\Request\RequestExpectation;
use T3G\LibTestHelper\Request\RequestPool;

class GithubRstIssueControllerTest extends AbstractFunctionalWebTestCase
{
    use AssertRequestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
    }

    public function testGithubIssueIsCreatedForRstChanges(): void
    {
        $rstFetchRawResponse = require __DIR__ . '/Fixtures/GithubRstIssuePatchFetchRawFileResponse.php';
        $rstFetchCompareResponse = require __DIR__ . '/Fixtures/GithubRstIssuePatchFetchCompareResponse.php';

        $requestPool = new RequestPool(
            new RequestExpectation(
                'GET',
                'https://raw.githubusercontent.com/TYPO3/TYPO3.CMS/1b5272038f09dd6f9d09736c8f57172c37d33648/typo3/sysext/core/Documentation/Changelog/12.0/Feature-97326-OpenBackendPageFromAdminPanel.rst',
                $rstFetchRawResponse
            ),
            new RequestExpectation(
                'GET',
                'https://api.github.com/repos/TYPO3/TYPO3.CMS/compare/cc9ce47f0c030772ca6b59bbc65d728fb32c96dd...47520511f4947a6ebd139a84e831a062a5b61c31',
                $rstFetchCompareResponse
            ),
            new RequestExpectation(
                'GET',
                'https://raw.githubusercontent.com/TYPO3/TYPO3.CMS/1b5272038f09dd6f9d09736c8f57172c37d33648/typo3/sysext/core/Documentation/Changelog/12.0/Feature-97326-OpenBackendPageFromAdminPanel.rst',
                $rstFetchRawResponse
            ),
            (new RequestExpectation(
                'POST',
                'https://api.github.com/repos/foobar-documentation/Changelog-To-Doc/issues',
                new Response(200)
            ))->withPayload([
                'headers' => [
                    'Authorization' => 'token 4711',
                ],
                'json' => [
                    'title' => '[BUGFIX] Load AdditionalFactoryConfiguration.php again',
                    'body' => file_get_contents(__DIR__ . '/Fixtures/GithubRstPushBody.txt'),
                    'labels' => ['12.0'],
                ],
            ]),
        );
        $generalClient = $this->createMock(Client::class);
        static::assertRequests($generalClient, $requestPool);

        $response = (new MockRequest($this->client))
            ->withMock('guzzle.client.general', $generalClient)
            ->execute(require __DIR__ . '/Fixtures/GithubRstIssuePatchRequest.php');
        $this->assertSame(SymfonyResponse::HTTP_OK, $response->getStatusCode());
    }

    public function testGithubIssueIsNotCreatedForChangesInNonMainBranch(): void
    {
        $generalClient = $this->createMock(Client::class);
        $generalClient->expects($this->never())->method('request');

        $response = (new MockRequest($this->client))
            ->withMock('guzzle.client.general', $generalClient)
            ->execute(require __DIR__ . '/Fixtures/GithubRstIssuePatchBackportRequest.php');
        $this->assertSame(SymfonyResponse::HTTP_OK, $response->getStatusCode());
    }

    public function testGithubIssueIsNotCreatedForChangesWithoutDocsChanges(): void
    {
        $generalClient = $this->createMock(Client::class);
        $generalClient->expects($this->never())->method('request');

        $response = (new MockRequest($this->client))
            ->withMock('guzzle.client.general', $generalClient)
            ->execute(require __DIR__ . '/Fixtures/GithubRstIssuePatchNoDocsChangesRequest.php');
        $this->assertSame(SymfonyResponse::HTTP_OK, $response->getStatusCode());
    }
}
