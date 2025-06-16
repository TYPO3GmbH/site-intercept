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
use App\Extractor\GithubPullRequestIssue;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class GithubPullRequestIssueTest extends TestCase
{
    private static array $body = [
        'title' => 'Pull request title',
        'body' => 'Pull request body',
        'html_url' => 'https://github.com/psychomieze/TYPO3.CMS/pull/1',
    ];

    public function testConstructorExtractsValues(): void
    {
        $response = new Response(200, ['Content-Type' => 'application/json'], json_encode(self::$body, JSON_THROW_ON_ERROR));
        $subject = new GithubPullRequestIssue($response);

        $this->assertSame('Pull request title', $subject->title);
        $this->assertSame('Pull request body', $subject->body);
        $this->assertSame('https://github.com/psychomieze/TYPO3.CMS/pull/1', $subject->url);
    }

    public function testConstructorThrowsIfDetailDataIsEmpty(): void
    {
        $this->expectException(DoNotCareException::class);

        $body = self::$body;
        $body['title'] = '';
        $response = new Response(200, ['Content-Type' => 'application/json'], json_encode($body, JSON_THROW_ON_ERROR));
        new GithubPullRequestIssue($response);
    }
}
