<?php
declare(strict_types = 1);
namespace App\Tests\Unit\Extractor;

use App\Exception\DoNotCareException;
use App\Extractor\GithubPushEventForSplit;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class GithubPushEventForSplitTest extends TestCase
{
    /**
     * @test
     */
    public function constructorHandlesMasterBranch()
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [],
            json_encode(['ref' => 'refs/heads/master'])
        );
        $subject = new GithubPushEventForSplit($request);
        $this->assertSame('master', $subject->sourceBranch);
        $this->assertSame('master', $subject->targetBranch);
    }

    /**
     * @test
     */
    public function constructorHandlesNineBranch()
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [],
            json_encode(['ref' => 'refs/heads/9.2'])
        );
        $subject = new GithubPushEventForSplit($request);
        $this->assertSame('9.2', $subject->sourceBranch);
        $this->assertSame('9.2', $subject->targetBranch);
    }

    /**
     * @test
     */
    public function constructorHandlesEightBranch()
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [],
            json_encode(['ref' => 'refs/heads/TYPO3_8-7'])
        );
        $subject = new GithubPushEventForSplit($request);
        $this->assertSame('TYPO3_8-7', $subject->sourceBranch);
        $this->assertSame('8.7', $subject->targetBranch);
    }

    /**
     * @test
     */
    public function constructorThrowsWithSevenBranch()
    {
        $this->expectException(DoNotCareException::class);
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [],
            json_encode(['ref' => 'refs/heads/TYPO3_7-6'])
        );
        new GithubPushEventForSplit($request);
    }

    /**
     * @test
     */
    public function constructorThrowsWithInvalidRef()
    {
        $this->expectException(DoNotCareException::class);
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [],
            json_encode(['ref' => 'refs/heads/'])
        );
        new GithubPushEventForSplit($request);
    }
}
