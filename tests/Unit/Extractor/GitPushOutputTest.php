<?php
declare(strict_types = 1);
namespace App\Tests\Unit\Extractor;

use App\Extractor\GitPushOutput;
use PHPUnit\Framework\TestCase;

class GitPushOutputTest extends TestCase
{
    /**
     * @test
     */
    public function constructorExtractsReviewUrl()
    {
        $subject = new GitPushOutput('some text https://review.typo3.org/12345 more text');
        $this->assertSame('https://review.typo3.org/12345', $subject->reviewUrl);
    }

    /**
     * @test
     */
    public function constructorThrowsIfUrlNotFound()
    {
        $this->expectException(\RuntimeException::class);
        new GitPushOutput('');
    }
}
