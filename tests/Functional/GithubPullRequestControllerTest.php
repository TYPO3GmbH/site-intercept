<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Functional;

use App\Bundle\TestDoubleBundle;
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
        $generalClientProphecy = $this->prophesize(GeneralClient::class);
        TestDoubleBundle::addProphecy('App\Client\GeneralClient', $generalClientProphecy);
        $forgeClientProphecy = $this->prophesize(ForgeClient::class);
        TestDoubleBundle::addProphecy('App\Client\ForgeClient', $forgeClientProphecy);
        $gitServiceProphecy = $this->prophesize(LocalCoreGitService::class);
        TestDoubleBundle::addProphecy('App\Service\LocalCoreGitService', $gitServiceProphecy);

        $generalClientProphecy
            ->get('https://api.github.com/repos/psychomieze/TYPO3.CMS/issues/1')
            ->shouldBeCalled()
            ->willReturn(require __DIR__ . '/Fixtures/GithubPullRequestIssueDetailsResponse.php');

        $generalClientProphecy
            ->get('https://api.github.com/users/psychomieze')
            ->shouldBeCalled()
            ->willReturn(require __DIR__ . '/Fixtures/GithubPullRequestUserDetailsResponse.php');

        $forgeIssueProphecy = $this->prophesize(Issue::class);
        $forgeClientProphecy->issue = $forgeIssueProphecy->reveal();
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
                        'value' => 10,
                    ]
                ]
            ])
            ->shouldBeCalled()
            ->willReturn(new \SimpleXMLElement('<?xml version="1.0"?><root><id>42</id></root>'));

        $generalClientProphecy
            ->get('https://github.com/psychomieze/TYPO3.CMS/pull/1.diff')
            ->shouldBeCalled()
            ->willReturn(require __DIR__ . '/Fixtures/GithubPullRequestPatchResponse.php');

        // Unmockable git foo
        $gitServiceProphecy->commitPatchAsUser(Argument::cetera())->shouldBeCalled();
        $pushOutput = $this->prophesize(GitPushOutput::class);
        $pushOutput->reviewUrl = 'https://review.typo3.org/12345';
        $gitServiceProphecy->pushToGerrit(Argument::cetera())->shouldBeCalled()->willReturn($pushOutput->reveal());

        $generalClientProphecy
            ->patch(
                'https://api.github.com/repos/psychomieze/TYPO3.CMS/pulls/1?access_token=4711',
                [
                    'json' => [
                        'state' => 'closed'
                    ]
                ]
            )
            ->shouldBeCalled();

        $generalClientProphecy
            ->post(
                'https://api.github.com/repos/psychomieze/TYPO3.CMS/issues/1/comments?access_token=4711',
                [
                    'json' => [
                        'body' => "Thank you for your contribution to TYPO3. We are using Gerrit Code Review for our contributions and took the liberty to convert your pull request to a review in our review system.\nYou can find your patch at: https://review.typo3.org/12345\nFor further information on how to contribute have a look at https://docs.typo3.org/typo3cms/ContributionWorkflowGuide/"
                    ]
                ]
            )
            ->shouldBeCalled();

        $generalClientProphecy
            ->put(
                'https://api.github.com/repos/psychomieze/TYPO3.CMS/issues/1/lock?access_token=4711'
            )
            ->shouldBeCalled();

        $kernel = new \App\Kernel('test', true);
        $kernel->boot();
        $request = require __DIR__ . '/Fixtures/GithubPullRequestGoodRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }

    /**
     * @test
     */
    public function pullRequestIsNotTransformed()
    {
        $generalClientProphecy = $this->prophesize(GeneralClient::class);
        TestDoubleBundle::addProphecy('App\Client\GeneralClient', $generalClientProphecy);
        $forgeClientProphecy = $this->prophesize(ForgeClient::class);
        TestDoubleBundle::addProphecy('App\Client\ForgeClient', $forgeClientProphecy);
        $gitServiceProphecy = $this->prophesize(LocalCoreGitService::class);
        TestDoubleBundle::addProphecy('App\Service\LocalCoreGitService', $gitServiceProphecy);

        $generalClientProphecy->get(Argument::cetera())->shouldNotBeCalled();

        $kernel = new \App\Kernel('test', true);
        $kernel->boot();
        $request = require __DIR__ . '/Fixtures/GithubPullRequestBadRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }
}
