<?php
declare(strict_types = 1);
namespace App\Extractor;

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use App\Exception\DoNotCareException;

/**
 * Extract information from a github push event hook
 * needed to trigger the core git split to single repos.
 *
 * Throws exceptions if not responsible.
 */
class GithubPushEventForSplit
{
    /**
     * @var string The source branch to split FROM, eg. 'TYPO3_8-7', '9.2', 'master'
     */
    public $sourceBranch;

    /**
     * @var string The target branch to split TO, eg. '8.7', '9.2', 'master'
     */
    public $targetBranch;

    /**
     * Extract information.
     *
     * @param string $payload
     * @throws DoNotCareException
     */
    public function __construct(string $payload)
    {
        $fullPullRequestInformation = json_decode($payload, true);
        $ref = $fullPullRequestInformation['ref'] ?? '';
        $this->sourceBranch = $this->getSourceBranch($ref);
        $this->targetBranch = $this->getTargetBranch($this->sourceBranch);
    }

    /**
     * Determine source branch from 'ref', eg. 'refs/heads/master' becomes 'master'
     *
     * @param string $ref
     * @return string
     * @throws DoNotCareException
     */
    private function getSourceBranch(string $ref): string
    {
        $sourceBranch = str_replace('refs/heads/', '', $ref);
        if (empty($sourceBranch)) {
            throw new DoNotCareException();
        }
        return $sourceBranch;
    }

    /**
     * Translate source branch of main git TYPO3.CMS repo to target branch name on split repos.
     *
     * @param string Source branch name
     * @return string Target branch name
     * @throws DoNotCareException
     */
    private function getTargetBranch(string $sourceBranch): string
    {
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
