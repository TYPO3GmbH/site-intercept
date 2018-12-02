<?php
declare(strict_types = 1);
namespace App\Tests\Unit\Extractor;

use App\Exception\DoNotCareException;
use App\Extractor\GithubPushEventForCore;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class GithubPushEventForCoreTest extends TestCase
{
    /**
     * @test
     */
    public function constructorHandlesPatchMasterBranch()
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
        $subject = new GithubPushEventForCore($request);
        $this->assertSame('master', $subject->sourceBranch);
        $this->assertSame('master', $subject->targetBranch);
        $this->assertSame('patch', $subject->type);
    }

    /**
     * @test
     */
    public function constructorHandlesPatchNineBranch()
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
        $subject = new GithubPushEventForCore($request);
        $this->assertSame('9.2', $subject->sourceBranch);
        $this->assertSame('9.2', $subject->targetBranch);
        $this->assertSame('patch', $subject->type);
    }

    /**
     * @test
     */
    public function constructorHandlesPatchEightBranch()
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
        $subject = new GithubPushEventForCore($request);
        $this->assertSame('TYPO3_8-7', $subject->sourceBranch);
        $this->assertSame('8.7', $subject->targetBranch);
        $this->assertSame('patch', $subject->type);
    }

    /**
     * @test
     */
    public function constructorHandlesTagNineBranch()
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [],
            json_encode([
                'ref' => 'refs/tags/v9.5.1',
                'created' => true,
                'base_ref' => 'refs/heads/9.5',
            ])
        );
        $subject = new GithubPushEventForCore($request);
        $this->assertSame('v9.5.1', $subject->tag);
        $this->assertSame('9.5', $subject->sourceBranch);
        $this->assertSame('9.5', $subject->targetBranch);
        $this->assertSame('tag', $subject->type);
    }

    /**
     * @test
     */
    public function constructorHandlesTagEightBranch()
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [],
            json_encode([
                'ref' => 'refs/tags/v8.7.42',
                'created' => true,
                'base_ref' => 'refs/heads/TYPO3_8-7',
            ])
        );
        $subject = new GithubPushEventForCore($request);
        $this->assertSame('v8.7.42', $subject->tag);
        $this->assertSame('TYPO3_8-7', $subject->sourceBranch);
        $this->assertSame('8.7', $subject->targetBranch);
        $this->assertSame('tag', $subject->type);
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
        new GithubPushEventForCore($request);
    }

    /**
     * @test
     */
    public function constructorThrowsWithEmptyRef()
    {
        $this->expectException(DoNotCareException::class);
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [],
            json_encode(['ref' => ''])
        );
        new GithubPushEventForCore($request);
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
        new GithubPushEventForCore($request);
    }

    /**
     * @test
     */
    public function constructorThrowsWithInvalidTagRef()
    {
        $this->expectException(DoNotCareException::class);
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [],
            json_encode([
                'ref' => 'refs/tags/',
                'created' => true,
                'base_ref' => 'refs/heads/9.5',
            ])
        );
        new GithubPushEventForCore($request);
    }

    /**
     * @test
     */
    public function constructorThrowsWithBrokenRef()
    {
        $this->expectException(DoNotCareException::class);
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [],
            json_encode(['ref' => 'refs/foo/'])
        );
        new GithubPushEventForCore($request);
    }
}
