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
use App\Extractor\GithubCorePullRequest;
use PHPUnit\Framework\TestCase;

class GithubCorePullRequestTest extends TestCase
{
    private $payload = [
        'action' => 'opened',
        'pull_request' => [
            'base' => [
                'ref' => 'main',
            ],
            'diff_url' => 'https://github.com/psychomieze/TYPO3.CMS/pull/1.diff',
            'user' => [
                'url' => 'https://api.github.com/users/psychomieze',
            ],
            'issue_url' => 'https://api.github.com/repos/psychomieze/TYPO3.CMS/issues/1',
            'url' => 'https://api.github.com/repos/psychomieze/TYPO3.CMS/pulls/1',
            'comments_url' => 'https://api.github.com/repos/psychomieze/TYPO3.CMS/issues/1/comments'
        ]
    ];

    /**
     * @test
     */
    public function constructorExtractsValues()
    {
        $subject = new GithubCorePullRequest(json_encode($this->payload));
        $this->assertSame('main', $subject->branch);
        $this->assertSame('https://github.com/psychomieze/TYPO3.CMS/pull/1.diff', $subject->diffUrl);
        $this->assertSame('https://api.github.com/users/psychomieze', $subject->userUrl);
        $this->assertSame('https://api.github.com/repos/psychomieze/TYPO3.CMS/issues/1', $subject->issueUrl);
        $this->assertSame('https://api.github.com/repos/psychomieze/TYPO3.CMS/pulls/1', $subject->pullRequestUrl);
        $this->assertSame('https://api.github.com/repos/psychomieze/TYPO3.CMS/issues/1/comments', $subject->commentsUrl);
    }

    /**
     * @test
     */
    public function constructorThrowsIfActionIsNotOpened()
    {
        $this->expectException(DoNotCareException::class);
        $payload = $this->payload;
        $payload['action'] = 'closed';
        new GithubCorePullRequest(json_encode($payload));
    }

    /**
     * @test
     */
    public function constructorThrowsIfDetailDataIsEmpty()
    {
        $this->expectException(DoNotCareException::class);
        $payload = $this->payload;
        $payload['pull_request']['diff_url'] = '';
        new GithubCorePullRequest(json_encode($payload));
    }
}
