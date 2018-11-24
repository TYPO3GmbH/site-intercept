<?php
declare(strict_types = 1);
namespace App\Tests\Unit\Extractor;

use App\Extractor\GitPatchFile;
use PHPUnit\Framework\TestCase;

class GitPatchFileTest extends TestCase
{
    /**
     * @test
     */
    public function constructorExtractsFilename()
    {
        $subject = new GitPatchFile('/foo/bar.txt');
        $this->assertSame('/foo/bar.txt', $subject->file);
    }

    /**
     * @test
     */
    public function constructorThrowsWithEmptyFile()
    {
        $this->expectException(\RuntimeException::class);
        new GitPatchFile('');
    }
}
