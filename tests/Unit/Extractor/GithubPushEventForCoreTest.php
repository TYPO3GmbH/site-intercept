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
        $subject = new GithubPushEventForCore(['ref' => 'refs/heads/master']);
        $this->assertSame('master', $subject->sourceBranch);
        $this->assertSame('master', $subject->targetBranch);
        $this->assertSame('patch', $subject->type);
    }

    /**
     * @test
     */
    public function constructorHandlesPatchNineBranch()
    {
        $subject = new GithubPushEventForCore(['ref' => 'refs/heads/9.2']);
        $this->assertSame('9.2', $subject->sourceBranch);
        $this->assertSame('9.2', $subject->targetBranch);
        $this->assertSame('patch', $subject->type);
    }

    /**
     * @test
     */
    public function constructorHandlesPatchEightBranch()
    {
        $subject = new GithubPushEventForCore(['ref' => 'refs/heads/TYPO3_8-7']);
        $this->assertSame('TYPO3_8-7', $subject->sourceBranch);
        $this->assertSame('8.7', $subject->targetBranch);
        $this->assertSame('patch', $subject->type);
    }

    /**
     * @test
     */
    public function constructorHandlesTagNineBranch()
    {
        $subject = new GithubPushEventForCore([
            'ref' => 'refs/tags/v9.5.1',
            'created' => true,
            'base_ref' => 'refs/heads/9.5',
        ]);
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
        $subject = new GithubPushEventForCore([
            'ref' => 'refs/tags/v8.7.42',
            'created' => true,
            'base_ref' => 'refs/heads/TYPO3_8-7',
        ]);
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
        new GithubPushEventForCore(['ref' => 'refs/heads/TYPO3_7-6']);
    }

    /**
     * @test
     */
    public function constructorThrowsWithEmptyRef()
    {
        $this->expectException(DoNotCareException::class);
        new GithubPushEventForCore(['ref' => '']);
    }

    /**
     * @test
     */
    public function constructorThrowsWithInvalidRef()
    {
        $this->expectException(DoNotCareException::class);
        new GithubPushEventForCore(['ref' => 'refs/heads/']);
    }

    /**
     * @test
     */
    public function constructorThrowsWithInvalidTagRef()
    {
        $this->expectException(DoNotCareException::class);
        new GithubPushEventForCore([
            'ref' => 'refs/tags/',
            'created' => true,
            'base_ref' => 'refs/heads/9.5',
        ]);
    }

    /**
     * @test
     */
    public function constructorThrowsWithBrokenRef()
    {
        $this->expectException(DoNotCareException::class);
        new GithubPushEventForCore(['ref' => 'refs/foo/']);
    }
}
