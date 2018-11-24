<?php
declare(strict_types = 1);
namespace App\Tests\Unit\Extractor;

use App\Exception\DoNotCareException;
use App\Extractor\GithubPushEventForSplit;
use PHPUnit\Framework\TestCase;

class GithubPushEventForSplitTest extends TestCase
{
    /**
     * @test
     */
    public function constructorHandlesMasterBranch()
    {
        $payload = [ 'ref' => 'refs/heads/master' ];
        $subject = new GithubPushEventForSplit(json_encode($payload));
        $this->assertSame('master', $subject->sourceBranch);
        $this->assertSame('master', $subject->targetBranch);
    }

    /**
     * @test
     */
    public function constructorHandlesNineBranch()
    {
        $payload = [ 'ref' => 'refs/heads/9.2' ];
        $subject = new GithubPushEventForSplit(json_encode($payload));
        $this->assertSame('9.2', $subject->sourceBranch);
        $this->assertSame('9.2', $subject->targetBranch);
    }

    /**
     * @test
     */
    public function constructorHandlesEightBranch()
    {
        $payload = [ 'ref' => 'refs/heads/TYPO3_8-7' ];
        $subject = new GithubPushEventForSplit(json_encode($payload));
        $this->assertSame('TYPO3_8-7', $subject->sourceBranch);
        $this->assertSame('8.7', $subject->targetBranch);
    }

    /**
     * @test
     */
    public function constructorThrowsWithSevenBranch()
    {
        $this->expectException(DoNotCareException::class);
        $payload = [ 'ref' => 'refs/heads/TYPO3_7-6' ];
        new GithubPushEventForSplit(json_encode($payload));
    }
}
