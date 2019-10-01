<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Extractor;

use App\Exception\DoNotCareException;

/**
 * Extract information from a github push event hook
 * that was triggered by a new github pull request on
 * https://github.com/TYPO3/TYPO3.CMS.
 * Throws exceptions if data is incomplete or not responsible.
 */
class GithubCorePullRequest
{
    /**
     * @var string Target PR branch, eg. 'master'
     */
    public $branch;

    /**
     * @var string Diff URL, eg. 'https://github.com/psychomieze/TYPO3.CMS/pull/1.diff'
     */
    public $diffUrl;

    /**
     * @var string URL to github user, eg. 'https://api.github.com/users/psychomieze'
     */
    public $userUrl;

    /**
     * @var string URL to pr "issue", eg. 'https://api.github.com/repos/psychomieze/TYPO3.CMS/issues/1'
     */
    public $issueUrl;

    /**
     * @var string URL to pull request, eg. 'https://api.github.com/repos/psychomieze/TYPO3.CMS/pulls/1'
     */
    public $pullRequestUrl;

    /**
     * @var string URL to pull request comments, eg. 'https://api.github.com/repos/psychomieze/TYPO3.CMS/issues/1/comments'
     */
    public $commentsUrl;

    /**
     * Extract information needed by pull request controller from a github
     * PR or throw an exception if not responsible.
     *
     * @param string $payload Incoming, not yet json_decoded payload
     * @throws DoNotCareException
     */
    public function __construct(string $payload)
    {
        $payload = json_decode($payload, true);
        $action = $payload['action'] ?? '';
        if ($action !== 'opened') {
            throw new DoNotCareException();
        }

        $this->branch = $payload['pull_request']['base']['ref'] ?? '';
        $this->diffUrl = $payload['pull_request']['diff_url'] ?? '';
        $this->userUrl = $payload['pull_request']['user']['url'] ?? '';
        $this->issueUrl = $payload['pull_request']['issue_url'] ?? '';
        $this->pullRequestUrl = $payload['pull_request']['url'] ?? '';
        $this->commentsUrl = $payload['pull_request']['comments_url'];

        // Do not care if pr information is not complete for whatever reason
        if (empty($this->branch) || empty($this->diffUrl) || empty($this->userUrl)
            || empty($this->issueUrl) || empty($this->pullRequestUrl) || empty($this->commentsUrl)
        ) {
            throw new DoNotCareException();
        }
    }
}
