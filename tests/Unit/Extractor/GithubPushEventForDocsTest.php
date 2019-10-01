<?php
declare(strict_types = 1);

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
    private $payload = [
        'ref' => 'refs/tags/1.2.3',
        'repository' => [
            'clone_url' => 'https://github.com/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript.git',
        ],
    ];

    /**
     * @test
     */
    public function constructorExtractsValues()
    {
        $subject = new GithubPushEventForDocs(json_encode($this->payload));
        $this->assertSame('1.2.3', $subject->tagOrBranchName);
        $this->assertSame('https://github.com/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript.git', $subject->repositoryUrl);
    }

    /**
     * @test
     */
    public function constructorExtractsFromBranch()
    {
        $payload = $this->payload;
        $payload['ref'] = 'refs/heads/latest';
        $subject = new GithubPushEventForDocs(json_encode($payload));
        $this->assertSame('latest', $subject->tagOrBranchName);
        $this->assertSame('https://github.com/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript.git', $subject->repositoryUrl);
    }

    /**
     * @test
     */
    public function constructorThrowsWithInvalidVersion()
    {
        $this->expectException(DoNotCareException::class);
        $payload = $this->payload;
        $payload['ref'] = 'refs/foo/latest';
        new GithubPushEventForDocs(json_encode($payload));
    }

    /**
     * @test
     */
    public function constructorThrowsWithEmptyRepository()
    {
        $this->expectException(DoNotCareException::class);
        $payload = $this->payload;
        $payload['repository']['clone_url'] = '';
        new GithubPushEventForDocs(json_encode($payload));
    }
}
