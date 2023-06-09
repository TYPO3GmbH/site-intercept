<?php

declare(strict_types=1);

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

class GithubPullRequestCloseCommentTest extends TestCase
{
    public function testMessageContainsRelevantInformation(): void
    {
        $pushOutput = new GitPushOutput('Foo https://review.typo3.org/c/Packages/TYPO3.CMS/+/58930/ bar');
        $subject = new GithubPullRequestCloseComment($pushOutput);
        self::assertStringContainsString('https://review.typo3.org/c/Packages/TYPO3.CMS/+/58930', $subject->comment);
    }
}
