<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Creator;

use App\Extractor\ForgeNewIssue;
use App\Extractor\GithubPullRequestIssue;

/**
 * Create a gerrit push commit message from github pull request
 * and forge issue information.
 */
class GerritCommitMessage
{
    private const MAX_CHARS_PER_LINE = 74;
    private const LF = "\n";
    private const DOUBLE_LF = "\n\n";

    /**
     * @var string The created commit message
     */
    public $message;

    /**
     * Create a decent commit message from the github pull request information
     * and the forge issue.
     *
     * @param GithubPullRequestIssue $githubIssue
     * @param ForgeNewIssue $forgeIssue
     */
    public function __construct(GithubPullRequestIssue $githubIssue, ForgeNewIssue $forgeIssue)
    {
        $subject = $this->formatSubject($githubIssue->title);
        $body = $this->formatBody($githubIssue->body);
        $releases = $this->getReleasesLine($body);
        $resolves = 'Resolves: #' . $forgeIssue->id;

        $this->message = $subject
            . self::DOUBLE_LF
            . $body . self::DOUBLE_LF
            . $releases . self::LF
            . $resolves;
    }

    /**
     * Try to extract a "Releases: xy" information from github issue body,
     * else fall back to master.
     *
     * @param string $body
     * @return string
     */
    private function getReleasesLine(string $body): string
    {
        $release = '';
        if (preg_match('/^Releases\:\s\w+$/m', $body) < 1) {
            $release = 'Releases: master';
        }
        return $release;
    }

    /**
     * Wrap commit body lines a bit.
     *
     * @param string $body
     * @return string
     */
    private function formatBody(string $body): string
    {
        return wordwrap($body, self::MAX_CHARS_PER_LINE, "\n", true);
    }

    /**
     * Try to format the github 'title' to a decent commit subject.
     *
     * @param string $subject
     * @return string
     */
    private function formatSubject(string $subject): string
    {
        if (preg_match('/^\[.+?\]/', $subject) < 1) {
            $subject = '[TASK] ' . $subject;
        }
        if (strlen($subject) > self::MAX_CHARS_PER_LINE) {
            $subject = substr($subject, 0, self::MAX_CHARS_PER_LINE);
        }
        return $subject;
    }
}
