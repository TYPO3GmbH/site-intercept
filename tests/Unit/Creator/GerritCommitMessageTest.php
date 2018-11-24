<?php
declare(strict_types = 1);
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
        $this->assertRegExp('/Patch title/', $subject->message);
        $this->assertRegExp('/Patch body/', $subject->message);
        $this->assertRegExp('/4711/', $subject->message);
    }
}
