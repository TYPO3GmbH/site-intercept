<?php
declare(strict_types = 1);

namespace T3G\Intercept\Github;
use T3G\Intercept\Exception\DoNotCareException;

/**
 * Class PushEvent
 *
 * @package T3G\Intercept\Github
 */
class PushEvent
{
    public $ref;

    /**
     * PullRequest constructor.
     *
     * @param string $requestPayload
     */
    public function __construct(string $requestPayload, Client $client = null)
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