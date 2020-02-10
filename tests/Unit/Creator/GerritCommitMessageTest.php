<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Unit\Creator;

use App\Creator\GerritCommitMessage;
use App\Extractor\ForgeNewIssue;
use App\Extractor\GithubPullRequestIssue;
use PHPUnit\Framework\TestCase;

class GerritCommitMessageTest extends TestCase
{
    /**
     * @test
     */
    public function messageContainsRelevantInformation()
    {
        $pullRequestProphecy = $this->prophesize(GithubPullRequestIssue::class);
        $pullRequestProphecy->title = 'Patch title';
        $pullRequestProphecy->body = 'Patch body';
        $forgeIssue = $this->prophesize(ForgeNewIssue::class);
        $forgeIssue->id = '4711';
        $subject = new GerritCommitMessage($pullRequestProphecy->reveal(), $forgeIssue->reveal());
        $this->assertRegExp('/^\[TASK\] Patch title/', $subject->message);
        $this->assertRegExp('/Patch body/', $subject->message);
        $this->assertRegExp('/4711/', $subject->message);
    }

    /**
     * @test
     */
    public function messageStripsLongTitle()
    {
        $pullRequestProphecy = $this->prophesize(GithubPullRequestIssue::class);
        $pullRequestProphecy->title = '0123456789012345678901234567890123456789012345678901234567890123456789';
        $pullRequestProphecy->body = 'Patch body';
        $forgeIssue = $this->prophesize(ForgeNewIssue::class);
        $forgeIssue->id = '4711';
        $subject = new GerritCommitMessage($pullRequestProphecy->reveal(), $forgeIssue->reveal());
        $this->assertRegExp('/^\[TASK\] 0123456789012345678901234567890123456789012345678901234567890123456/', $subject->message);
        $this->assertRegExp('/Patch body/', $subject->message);
        $this->assertRegExp('/4711/', $subject->message);
    }

    /**
     * @test
     */
    public function messageKeepsTitlePrefix()
    {
        $pullRequestProphecy = $this->prophesize(GithubPullRequestIssue::class);
        $pullRequestProphecy->title = '[BUGFIX] Patch title';
        $pullRequestProphecy->body = 'Patch body';
        $forgeIssue = $this->prophesize(ForgeNewIssue::class);
        $forgeIssue->id = '4711';
        $subject = new GerritCommitMessage($pullRequestProphecy->reveal(), $forgeIssue->reveal());
        $this->assertRegExp('/^\[BUGFIX\] Patch title/', $subject->message);
        $this->assertRegExp('/Patch body/', $subject->message);
        $this->assertRegExp('/4711/', $subject->message);
    }
}
