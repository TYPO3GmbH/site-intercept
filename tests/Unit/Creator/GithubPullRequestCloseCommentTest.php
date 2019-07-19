<?php
declare(strict_types = 1);
namespace App\Tests\Unit\Creator;

use App\Creator\GithubPullRequestCloseComment;
use App\Extractor\GitPushOutput;
use PHPUnit\Framework\TestCase;

class GithubPullRequestCloseCommentTest extends TestCase
{
    /**
     * @test
     */
    public function messageContainsRelevantInformation()
    {
        $pushOutput = $this->prophesize(GitPushOutput::class);
        $pushOutput->reviewUrl = 'https://review.typo3.org/#/c/58930/';
        $subject = new GithubPullRequestCloseComment($pushOutput->reveal());
        $this->assertRegExp('/https:\/\/review.typo3.org\/#\/c\/58930\//', $subject->comment);
    }
}
