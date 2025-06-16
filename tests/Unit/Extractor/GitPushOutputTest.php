<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Unit\Extractor;

use App\Extractor\GitPushOutput;
use PHPUnit\Framework\TestCase;

class GitPushOutputTest extends TestCase
{
    public function testConstructorExtractsReviewUrl(): void
    {
        $exampleOutput = 'Enumerating objects: 5, done.
Counting objects: 100% (5/5), done.
Delta compression using up to 12 threads
Compressing objects: 100% (3/3), done.
Writing objects: 100% (3/3), 668 bytes | 668.00 KiB/s, done.
Total 3 (delta 2), reused 0 (delta 0)
remote: Resolving deltas: 100% (2/2)
remote: Processing changes: refs: 1, new: 1, done
remote:
remote: SUCCESS
remote:
remote: New Changes:
remote:   https://review.typo3.org/c/Packages/TYPO3.CMS/+/60480 [WIP][TASK] testing gerrit [WIP]
To ssh://review.typo3.org:29418/Packages/TYPO3.CMS.git
 * [new branch]            HEAD -> refs/for/main%wip';
        $subject = new GitPushOutput($exampleOutput);
        $this->assertSame('https://review.typo3.org/c/Packages/TYPO3.CMS/+/60480', $subject->reviewUrl);
    }

    public function testConstructorThrowsIfUrlNotFound(): void
    {
        $this->expectException(\RuntimeException::class);
        new GitPushOutput('');
    }
}
