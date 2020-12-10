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
use App\Extractor\GithubPullRequestIssue;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class GithubPullRequestIssueTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;
    private $body = [
        'title' => 'Pull request title',
        'body' => 'Pull request body',
        'html_url' => 'https://github.com/psychomieze/TYPO3.CMS/pull/1',
    ];

    /**
     * @test
     */
    public function constructorExtractsValues()
    {
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn(json_encode($this->body));
        $subject = new GithubPullRequestIssue($responseProphecy->reveal());
        $this->assertSame('Pull request title', $subject->title);
        $this->assertSame('Pull request body', $subject->body);
        $this->assertSame('https://github.com/psychomieze/TYPO3.CMS/pull/1', $subject->url);
    }

    /**
     * @test
     */
    public function constructorThrowsIfDetailDataIsEmpty()
    {
        $this->expectException(DoNotCareException::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $body = $this->body;
        $body['title'] = '';
        $responseProphecy->getBody()->willReturn(json_encode($body));
        new GithubPullRequestIssue($responseProphecy->reveal());
    }
}
