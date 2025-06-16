<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Unit\Creator;

use App\Creator\GerritCommitMessage;
use App\Extractor\ForgeNewIssue;
use App\Extractor\GithubPullRequestIssue;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class GerritCommitMessageTest extends TestCase
{
    public function testMessageContainsRelevantInformation(): void
    {
        $response = new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'title' => 'Patch title',
            'body' => 'Patch body',
            'html_url' => 'https://github.com/TYPO3/typo3/pull/42',
        ], JSON_THROW_ON_ERROR));
        $pullRequest = new GithubPullRequestIssue($response);

        $xml = new \SimpleXMLElement('<?xml version="1.0" standalone="yes"?><root><id>4711</id></root>');
        $forgeIssue = new ForgeNewIssue($xml);

        $subject = new GerritCommitMessage($pullRequest, $forgeIssue);
        $this->assertStringContainsString('[TASK] Patch title', $subject->message);
        $this->assertStringContainsString('Patch body', $subject->message);
        $this->assertStringContainsString('Resolves: #4711', $subject->message);
    }

    public function testMessageStripsLongTitle(): void
    {
        $response = new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'title' => '0123456789012345678901234567890123456789012345678901234567890123456789',
            'body' => 'Patch body',
            'html_url' => 'https://github.com/TYPO3/typo3/pull/42',
        ], JSON_THROW_ON_ERROR));
        $pullRequest = new GithubPullRequestIssue($response);

        $xml = new \SimpleXMLElement('<?xml version="1.0" standalone="yes"?><root><id>4711</id></root>');
        $forgeIssue = new ForgeNewIssue($xml);

        $subject = new GerritCommitMessage($pullRequest, $forgeIssue);
        $this->assertStringContainsString('[TASK] 0123456789012345678901234567890123456789012345678901234567890123456', $subject->message);
        $this->assertStringContainsString('Patch body', $subject->message);
        $this->assertStringContainsString('Resolves: #4711', $subject->message);
    }

    public function testMessageKeepsTitlePrefix(): void
    {
        $response = new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'title' => '[BUGFIX] Patch title',
            'body' => 'Patch body',
            'html_url' => 'https://github.com/TYPO3/typo3/pull/42',
        ], JSON_THROW_ON_ERROR));
        $pullRequest = new GithubPullRequestIssue($response);

        $xml = new \SimpleXMLElement('<?xml version="1.0" standalone="yes"?><root><id>4711</id></root>');
        $forgeIssue = new ForgeNewIssue($xml);

        $subject = new GerritCommitMessage($pullRequest, $forgeIssue);
        $this->assertStringContainsString('[BUGFIX] Patch title', $subject->message);
        $this->assertStringContainsString('Patch body', $subject->message);
        $this->assertStringContainsString('Resolves: #4711', $subject->message);
    }
}
