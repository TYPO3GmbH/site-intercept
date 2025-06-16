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
use App\Extractor\GithubPushEventForDocs;
use PHPUnit\Framework\TestCase;

class GithubPushEventForDocsTest extends TestCase
{
    private static array $payload = [
        'ref' => 'refs/tags/1.2.3',
        'repository' => [
            'clone_url' => 'https://github.com/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript.git',
        ],
    ];

    public function testConstructorExtractsValues(): void
    {
        $subject = new GithubPushEventForDocs(json_encode(self::$payload, JSON_THROW_ON_ERROR));
        $this->assertSame('1.2.3', $subject->tagOrBranchName);
        $this->assertSame('https://github.com/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript.git', $subject->repositoryUrl);
    }

    public function testConstructorExtractsFromBranch(): void
    {
        $payload = self::$payload;
        $payload['ref'] = 'refs/heads/latest';
        $subject = new GithubPushEventForDocs(json_encode($payload, JSON_THROW_ON_ERROR));
        $this->assertSame('latest', $subject->tagOrBranchName);
        $this->assertSame('https://github.com/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript.git', $subject->repositoryUrl);
    }

    public function testConstructorThrowsWithInvalidVersion(): void
    {
        $this->expectException(DoNotCareException::class);
        $payload = self::$payload;
        $payload['ref'] = 'refs/foo/latest';
        new GithubPushEventForDocs(json_encode($payload, JSON_THROW_ON_ERROR));
    }

    public function testConstructorThrowsWithEmptyRepository(): void
    {
        $this->expectException(DoNotCareException::class);
        $payload = self::$payload;
        $payload['repository']['clone_url'] = '';
        new GithubPushEventForDocs(json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
