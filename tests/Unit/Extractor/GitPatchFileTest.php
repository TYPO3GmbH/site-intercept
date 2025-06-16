<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Unit\Extractor;

use App\Extractor\GitPatchFile;
use PHPUnit\Framework\TestCase;

class GitPatchFileTest extends TestCase
{
    public function testConstructorExtractsFilename(): void
    {
        $subject = new GitPatchFile('/foo/bar.txt');
        $this->assertSame('/foo/bar.txt', $subject->file);
    }

    public function testConstructorThrowsWithEmptyFile(): void
    {
        $this->expectException(\RuntimeException::class);

        new GitPatchFile('');
    }
}
