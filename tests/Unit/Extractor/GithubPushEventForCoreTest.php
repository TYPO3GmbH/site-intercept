<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Unit\Extractor;

use App\Exception\DoNotCareException;
use App\Extractor\GithubPushEventForCore;
use PHPUnit\Framework\TestCase;

class GithubPushEventForCoreTest extends TestCase
{
    public function testConstructorHandlesPatchMainBranch(): void
    {
        $subject = new GithubPushEventForCore(['ref' => 'refs/heads/main']);
        $this->assertSame('main', $subject->sourceBranch);
        $this->assertSame('main', $subject->targetBranch);
        $this->assertSame('patch', $subject->type);
    }

    public function testConstructorHandlesPatchNineBranch(): void
    {
        $subject = new GithubPushEventForCore(['ref' => 'refs/heads/9.2']);
        $this->assertSame('9.2', $subject->sourceBranch);
        $this->assertSame('9.2', $subject->targetBranch);
        $this->assertSame('patch', $subject->type);
    }

    public function testConstructorHandlesTagNineBranch(): void
    {
        $subject = new GithubPushEventForCore([
            'ref' => 'refs/tags/v9.5.1',
            'created' => true,
        ]);
        $this->assertSame('v9.5.1', $subject->tag);
        $this->assertSame('tag', $subject->type);
    }

    public function testConstructorThrowsWithEmptyRef(): void
    {
        $this->expectException(DoNotCareException::class);
        new GithubPushEventForCore(['ref' => '']);
    }

    public function testConstructorThrowsWithInvalidRef(): void
    {
        $this->expectException(DoNotCareException::class);
        new GithubPushEventForCore(['ref' => 'refs/heads/']);
    }

    public function testConstructorThrowsWithInvalidTagRef(): void
    {
        $this->expectException(DoNotCareException::class);
        new GithubPushEventForCore([
            'ref' => 'refs/tags/',
            'created' => true,
        ]);
    }

    public function testConstructorThrowsWithBrokenRef(): void
    {
        $this->expectException(DoNotCareException::class);
        new GithubPushEventForCore(['ref' => 'refs/foo/']);
    }
}
