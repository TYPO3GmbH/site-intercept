<?php
declare(strict_types = 1);
namespace App\Tests\Functional;

use App\Client\ForgeClient;
use App\Client\GeneralClient;
use App\Extractor\GitPushOutput;
use App\Service\LocalCoreGitService;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Redmine\Api\Issue;

class GithubPullRequestControllerTest extends TestCase
{
    /**
     * @test
     */
    public function pullRequestIsTransformed()
    {
        $kernel = new \App\Kernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer();

        $generalClient = $this->prophesize(GeneralClient::class);
        $container->set('App\Client\GeneralClient', $generalClient->reveal());
        $forgeClient = $this->prophesize(ForgeClient::class);
        $container->set('App\Client\ForgeClient', $forgeClient->reveal());
        $gitService = $this->prophesize(LocalCoreGitService::class);
        $container->set('App\Service\LocalCoreGitService', $gitService->reveal());

        $generalClient
            ->get('https://api.github.com/repos/psychomieze/TYPO3.CMS/issues/1')
            ->shouldBeCalled()
            ->willReturn(require __DIR__ . '/Fixtures/GithubPullRequestIssueDetailsResponse.php');

        $generalClient
            ->get('https://api.github.com/users/psychomieze')
            ->shouldBeCalled()
            ->willReturn(require __DIR__ . '/Fixtures/GithubPullRequestUserDetailsResponse.php');

        $forgeIssueProphecy = $this->prophesize(Issue::class);
        $forgeClient->issue = $forgeIssueProphecy->reveal();
        $forgeIssueProphecy
            ->create([
                'project_id' => 27,
                'tracker_id' => 4,
                'subject' => 'issue title',
                'description' =>"updated body\n\nThis issue was automatically created from https://github.com/psychomieze/TYPO3.CMS/pull/1",
                'custom_fields' => [
                    [
                        'id' => 4,
                        'name' => 'TYPO3 Version',
                        'value' => 8,
                    ]
                ]
            ])
            ->shouldBeCalled()
            ->willReturn(new \SimpleXMLElement('<?xml version="1.0"?><root><id>42</id></root>'));

        $generalClient
            ->get('https://github.com/psychomieze/TYPO3.CMS/pull/1.diff')
            ->shouldBeCalled()
            ->willReturn(require __DIR__ . '/Fixtures/GithubPullRequestPatchResponse.php');

        // Unmockable git foo
        $gitService->commitPatchAsUser(Argument::cetera())->shouldBeCalled();
        $pushOutput = $this->prophesize(GitPushOutput::class);
        $pushOutput->reviewUrl = 'https://review.typo3.org/12345';
        $gitService->pushToGerrit(Argument::cetera())->shouldBeCalled()->willReturn($pushOutput->reveal());

        $generalClient
            ->patch(
                'https://api.github.com/repos/psychomieze/TYPO3.CMS/pulls/1?access_token=4711',
                [
                    'json' => [
                        'state' => 'closed'
                    ]
                ]
            )
            ->shouldBeCalled();

        $generalClient
            ->post(
                'https://api.github.com/repos/psychomieze/TYPO3.CMS/issues/1/comments?access_token=4711',
                [
                    'json' => [
                        'body' => "Thank you for your contribution to TYPO3. We are using Gerrit Code Review for our contributions and took the liberty to convert your pull request to a review in our review system.\nYou can find your patch at: https://review.typo3.org/12345\nFor further information on how to contribute have a look at https://docs.typo3.org/typo3cms/ContributionWorkflowGuide/"
                    ]
                ]
            )
            ->shouldBeCalled();

        $generalClient
            ->put(
                'https://api.github.com/repos/psychomieze/TYPO3.CMS/issues/1/lock?access_token=4711'
            )
            ->shouldBeCalled();

        $request = require __DIR__ . '/Fixtures/GithubPullRequestGoodRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }

    /**
     * @test
     */
    public function pullRequestIsNotTransformed()
    {
        $kernel = new \App\Kernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer();

        $generalClient = $this->prophesize(GeneralClient::class);
        $container->set('App\Client\GeneralClient', $generalClient->reveal());
        $forgeClient = $this->prophesize(ForgeClient::class);
        $container->set('App\Client\ForgeClient', $forgeClient->reveal());
        $gitService = $this->prophesize(LocalCoreGitService::class);
        $container->set('App\Service\LocalCoreGitService', $gitService->reveal());

        $generalClient->get(Argument::cetera())->shouldNotBeCalled();

        $request = require __DIR__ . '/Fixtures/GithubPullRequestBadRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }
}
