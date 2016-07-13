<?php

declare(strict_types = 1);

namespace T3G\Intercept\Tests\Unit\Github;

use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use T3G\Intercept\Github\Client;
use T3G\Intercept\Github\PullRequest;

class PullRequestTest extends \PHPUnit_Framework_TestCase
{
    protected $githubClient;
    /**
     * @var string
     */
    protected $requestPayload = '';

    /**
     * @var \T3G\Intercept\Github\PullRequest
     */
    protected $githubPullRequest;

    public function setUp()
    {
        $this->requestPayload = file_get_contents(BASEPATH . '/Tests/Fixtures/GithubPullRequestHookPayload.json');
        $this->githubClient = $this->prophesize(Client::class);
        $this->githubPullRequest = new PullRequest($this->requestPayload, $this->githubClient->reveal());
        $GLOBALS['gitOutput'] = '
Counting objects: 12, done.
Delta compression using up to 8 threads.
Compressing objects: 100% (12/12), done.
Writing objects: 100% (12/12), 2.52 KiB | 0 bytes/s, done.
Total 12 (delta 9), reused 0 (delta 0)
remote: Resolving deltas: 100% (9/9)
remote: Processing changes: updated: 1, refs: 1, done    
remote: 
remote: Updated Changes:
remote:   https://review.typo3.org/48929 [TASK] Move arguments to initializeArguments() in CObjectVH in ext:fluid
remote: 
To ssh://maddy2101@review.typo3.org:29418/Packages/TYPO3.CMS.git
 * [new branch]      HEAD -> refs/publish/master
        ';

    }

    /**
     * @test
     * @return void
     */
    public function transformExtractsPatchUrl()
    {
        self::assertSame('https://github.com/psychomieze/TYPO3.CMS/pull/1.diff', $this->githubPullRequest->diffUrl);
    }

    /**
     * @test
     * @return void
     */
    public function transformExtractsIssueUrl()
    {
        self::assertSame('https://api.github.com/repos/psychomieze/TYPO3.CMS/issues/1', $this->githubPullRequest->issueUrl);
    }

    /**
     * @test
     * @return void
     */
    public function transformExtractsUserUrl()
    {
        self::assertSame('https://api.github.com/users/psychomieze', $this->githubPullRequest->userUrl);
    }

    /**
     * @test
     * @return void
     */
    public function transformExtractsPullRequestUrl()
    {
        self::assertSame('https://api.github.com/repos/psychomieze/TYPO3.CMS/pulls/1', $this->githubPullRequest->pullRequestUrl);
    }

    /**
     * @test
     * @return void
     */
    public function transformExtractsCommentsUrl()
    {
        self::assertSame('https://api.github.com/repos/psychomieze/TYPO3.CMS/issues/1/comments', $this->githubPullRequest->commentsUrl);
    }


    /**
     * @test
     * @return void
     */
    public function closePullRequestSetsStatusToClosed()
    {
        $this->githubClient->patch(Argument::cetera())->willReturn(new Response());
        $this->githubClient->post(Argument::cetera())->willReturn(new Response());
        $this->githubClient->put(Argument::cetera())->willReturn(new Response());

        $this->githubPullRequest->closePullRequest();

        $this->githubClient->patch(Argument::any(), Argument::containing('closed'))->shouldHaveBeenCalled();
    }

    /**
     * @test
     * @return void
     */
    public function closePullRequestCommentContainsLinkToReview()
    {
        $this->githubClient->patch(Argument::cetera())->willReturn(new Response());
        $this->githubClient->post(Argument::cetera())->willReturn(new Response());
        $this->githubClient->put(Argument::cetera())->willReturn(new Response());

        $this->githubPullRequest->closePullRequest();

        $this->githubClient->post(Argument::any(), Argument::any(), Argument::containingString('https://review.typo3.org/48929'))->shouldHaveBeenCalled();
    }

    /**
     * @test
     * @return void
     */
    public function closePullRequestCommentContainsLinkToContributionWorkflow()
    {
        $contributionLink = 'https://docs.typo3.org/typo3cms/ContributionWorkflowGuide/';

        $this->githubClient->patch(Argument::cetera())->willReturn(new Response());
        $this->githubClient->post(Argument::cetera())->willReturn(new Response());
        $this->githubClient->put(Argument::cetera())->willReturn(new Response());

        $this->githubPullRequest->closePullRequest();

        $this->githubClient->post(
            Argument::any(),
            Argument::any(),
            Argument::containingString($contributionLink)
        )->shouldHaveBeenCalled();;
    }

    /**
     * @test
     * @return void
     */
    public function closePullRequestLocksComments()
    {
        $this->githubClient->patch(Argument::cetera())->willReturn(new Response());
        $this->githubClient->post(Argument::cetera())->willReturn(new Response());
        $this->githubClient->put(Argument::cetera())->willReturn(new Response());

        $this->githubPullRequest->closePullRequest();

        $this->githubClient->put(
            Argument::containingString('lock')
        )->shouldHaveBeenCalled();;
    }

    /**
     * @test
     * @return void
     */
    public function getIssueDataFetchesIssueDataAndTransformsIt()
    {
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn(file_get_contents(BASEPATH . '/Tests/Fixtures/GithubIssueInformation.json'));
        $this->githubClient->get(Argument::cetera())->willReturn($responseProphecy->reveal());
        $issueData = $this->githubPullRequest->getIssueData();

        $this->githubClient->get($this->githubPullRequest->issueUrl)->shouldHaveBeenCalled();
        self::assertSame('issue title', $issueData['title']);
    }

    /**
     * @test
     * @return void
     */
    public function getUserDataFetchesUserDataAndTransformsIt()
    {
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn(
            file_get_contents(BASEPATH . '/Tests/Fixtures/GithubUserInformation.json')
        );
        $this->githubClient->get(Argument::cetera())->willReturn($responseProphecy->reveal());
        $issueData = $this->githubPullRequest->getUserData();

        $this->githubClient->get($this->githubPullRequest->userUrl)->shouldHaveBeenCalled();
        self::assertSame('psychomieze', $issueData['user']);
    }
}
