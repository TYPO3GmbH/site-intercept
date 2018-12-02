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
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;

/**
 * Extract information from a github push event hook
 * needed to trigger the core git split to single repos.
 *
 * Throws exceptions if not responsible.
 */
class GithubPushEventForCore
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
     * @var string Used especially in logging as context.
     */
    public $jobUuid;

    /**
     * Extract information.
     *
     * @param Request $request
     * @throws DoNotCareException
     * @throws \Exception
     */
    public function __construct(Request $request = null)
    {
        if ($request) {
            $fullPullRequestInformation = json_decode($request->getContent(), true);
            $ref = $fullPullRequestInformation['ref'] ?? '';
            $this->sourceBranch = $this->getSourceBranch($ref);
            $this->targetBranch = $this->getTargetBranch($this->sourceBranch);
            if (empty($jobUuid)) {
                $jobUuid = Uuid::uuid4()->toString();
            }
            $this->jobUuid = $jobUuid;
        }
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
