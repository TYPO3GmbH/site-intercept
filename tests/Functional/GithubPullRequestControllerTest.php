<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Functional;

use App\Extractor\GitPushOutput;
use App\Service\LocalCoreGitService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Redmine\Api\Issue;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use T3G\LibTestHelper\Request\AssertRequestTrait;
use T3G\LibTestHelper\Request\MockRequest;
use T3G\LibTestHelper\Request\RequestExpectation;
use T3G\LibTestHelper\Request\RequestPool;

class GithubPullRequestControllerTest extends AbstractFunctionalWebTestCase
{
    use AssertRequestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
    }

    public function testPullRequestIsTransformed(): void
    {
        $generalClientRequestPool = new RequestPool(
            new RequestExpectation(
                'GET',
                'https://api.github.com/repos/psychomieze/TYPO3.CMS/issues/1',
                require __DIR__ . '/Fixtures/GithubPullRequestIssueDetailsResponse.php'
            ),
            new RequestExpectation(
                'GET',
                'https://api.github.com/users/psychomieze',
                require __DIR__ . '/Fixtures/GithubPullRequestUserDetailsResponse.php'
            ),
            new RequestExpectation(
                'GET',
                'https://github.com/psychomieze/TYPO3.CMS/pull/1.diff',
                require __DIR__ . '/Fixtures/GithubPullRequestPatchResponse.php'
            ),
            (new RequestExpectation(
                'PATCH',
                'https://api.github.com/repos/psychomieze/TYPO3.CMS/pulls/1',
                new Response(SymfonyResponse::HTTP_OK)
            ))->withPayload([
                'headers' => [
                    'Authorization' => 'token 4711',
                ],
                'json' => [
                    'state' => 'closed',
                ],
            ]),
            (new RequestExpectation(
                'POST',
                'https://api.github.com/repos/psychomieze/TYPO3.CMS/issues/1/comments',
                new Response(SymfonyResponse::HTTP_OK)
            ))->withPayload([
                'headers' => [
                    'Authorization' => 'token 4711',
                ],
                'json' => [
                    'body' => "Thank you for your contribution to TYPO3. We are using Gerrit Code Review for our contributions and took the liberty to convert your pull request to a review in our review system.\nYou can find your patch at: https://review.typo3.org/c/Packages/TYPO3.CMS/+/12345\nFor further information on how to contribute have a look at https://docs.typo3.org/typo3cms/ContributionWorkflowGuide/",
                ],
            ]),
            (new RequestExpectation(
                'PUT',
                'https://api.github.com/repos/psychomieze/TYPO3.CMS/issues/1/lock',
                new Response(SymfonyResponse::HTTP_OK)
            ))->withPayload([
                'headers' => [
                    'Authorization' => 'token 4711',
                ],
            ]),
        );
        $generalClient = $this->createMock(Client::class);
        static::assertRequests($generalClient, $generalClientRequestPool);

        $forgeClient = $this->createMock(\Redmine\Client\Client::class);
        $forgeIssueApi = $this->createMock(Issue::class);
        $forgeIssueApi->expects($this->once())->method('create')->with([
            'project_id' => 27,
            'tracker_id' => 4,
            'subject' => 'issue title',
            'description' => "updated body\n\nThis issue was automatically created from https://github.com/psychomieze/TYPO3.CMS/pull/1",
            'custom_fields' => [
                [
                    'id' => 4,
                    'name' => 'TYPO3 Version',
                    'value' => 12,
                ],
            ],
        ])->willReturn(new \SimpleXMLElement('<?xml version="1.0"?><root><id>42</id></root>'));
        $forgeClient->expects($this->once())->method('getApi')->with('issue')->willReturn($forgeIssueApi);

        $gitService = $this->createMock(LocalCoreGitService::class);
        $gitService->expects($this->once())->method('commitPatchAsUser');
        $gitService->expects($this->once())->method('pushToGerrit')->willReturn(new GitPushOutput('https://review.typo3.org/c/Packages/TYPO3.CMS/+/12345'));

        $response = (new MockRequest($this->client))
            ->withMock('guzzle.client.general', $generalClient)
            ->withMock('redmine.client.forge', $forgeClient)
            ->withMock(LocalCoreGitService::class, $gitService)
            ->execute(require __DIR__ . '/Fixtures/GithubPullRequestGoodRequest.php');
        $this->assertSame(SymfonyResponse::HTTP_OK, $response->getStatusCode());
    }

    public function testPullRequestIsNotTransformed(): void
    {
        $generalClient = $this->createMock(Client::class);
        $generalClient->expects($this->never())->method('request');

        $response = (new MockRequest($this->client))
            ->withMock('guzzle.client.general', $generalClient)
            ->execute(require __DIR__ . '/Fixtures/GithubPullRequestBadRequest.php');
        $this->assertSame(SymfonyResponse::HTTP_OK, $response->getStatusCode());
    }
}
