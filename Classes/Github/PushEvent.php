<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/build-information-service.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Intercept\Github;

use T3G\Intercept\Exception\DoNotCareException;

/**
 * Class PushEvent
 *
 */
class PushEvent
{
    public $ref;

    /**
     * PullRequest constructor.
     *
     * @param string $requestPayload
     */
    public function __construct(string $requestPayload)
    {
        $fullPullRequestInformation = json_decode($requestPayload, true);
        $this->ref = $fullPullRequestInformation['ref'];
    }

    /**
     * Split source branch name off from ref
     *
     * @return string
     */
    public function getBranchName(): string
    {
        $lengthRefsHeads = strlen('refs/heads/');
        return (string)substr($this->ref, $lengthRefsHeads, strlen($this->ref) - $lengthRefsHeads);
    }

    /**
     * Translate source branch of main git TYPO3.CMS repo to target branch name on split repos
     *
     * @return string target branch name
     * @throws DoNotCareException
     */
    public function getTargetBranch(): string
    {
        $sourceBranch = $this->getBranchName();
        if ($sourceBranch === 'master') {
            return $sourceBranch;
        } elseif ($sourceBranch === 'TYPO3_8-7') {
            return '8.7';
        } elseif (preg_match('/[0-9]+\.[0-9]+/', $sourceBranch)) {
            return $sourceBranch;
        } else {
            throw new DoNotCareException();
        }
    }
}
