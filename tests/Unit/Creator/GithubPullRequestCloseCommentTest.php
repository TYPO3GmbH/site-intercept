<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Unit\Creator;

use App\Creator\GithubPullRequestCloseComment;
use App\Extractor\GitPushOutput;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class GithubPullRequestCloseCommentTest extends TestCase
{
    use ProphecyTrait;
    /**
     * @test
     */
    public function messageContainsRelevantInformation()
    {
        $pushOutput = $this->prophesize(GitPushOutput::class);
        $pushOutput->reviewUrl = 'https://review.typo3.org/#/c/58930/';
        $subject = new GithubPullRequestCloseComment($pushOutput->reveal());
        $this->assertMatchesRegularExpression('/https:\/\/review.typo3.org\/#\/c\/58930\//', $subject->comment);
    }
}
