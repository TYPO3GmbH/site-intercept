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
 * needed to trigger a bamboo docs render build
 */
class GithubPushEventForDocs
{
    /**
     * @var string A tag or a branch name
     */
    public $versionNumber = '';

    /**
     * @var string Full repository url to clone
     */
    public $repositoryUrl = '';

    /**
     * Extract information needed by docs trigger from a github PR
     * or throw an exception if not responsible
     *
     * @param string $payload
     * @throws DoNotCareException
     */
    public function __construct(string $payload)
    {
        $fullPullRequestInformation = json_decode($payload, true);
        $this->versionNumber = $this->getVersionNumberFromRef($fullPullRequestInformation['ref']);
        $this->repositoryUrl = $fullPullRequestInformation['repository']['clone_url'];
        if (empty($this->versionNumber) || empty($this->repositoryUrl)) {
            throw new DoNotCareException();
        }
    }

    /**
     * Find branch or tag name
     *
     * @param string $ref
     * @return string
     * @throws DoNotCareException
     */
    private function getVersionNumberFromRef(string $ref): string
    {
        if (strpos($ref, 'refs/tags/') === 0) {
            return str_replace('refs/tags/', '', $ref);
        }
        if (strpos($ref, 'refs/heads/') === 0) {
            return str_replace('refs/heads/', '', $ref);
        }
        throw new DoNotCareException();
    }
}
