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
 * needed to trigger a bamboo docs render build
 */
class GithubPushEventForDocs
{
    /**
     * @var string A tag or a branch name
     */
    public string $tagOrBranchName = '';

    /**
     * @var string Repository url to clone, eg. 'https://github.com/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript.git'
     */
    public string $repositoryUrl = '';

    /**
     * Path to composer.json in repository
     */
    public string $composerFile = '';

    /**
     * Extract information needed by docs trigger from a github
     * push event or throw an exception if not responsible
     *
     * @param string $payload
     * @throws DoNotCareException
     */
    public function __construct(string $payload)
    {
        $payload = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        $this->tagOrBranchName = $this->getTagOrBranchFromRef($payload['ref']);
        $this->repositoryUrl = $payload['repository']['clone_url'];
        $repositoryName = $this->extractRepositoryNameFromUrl($this->repositoryUrl);
        $this->composerFile = 'https://raw.githubusercontent.com/' . $repositoryName . '/' . $this->tagOrBranchName . '/composer.json';
        if (empty($this->tagOrBranchName) || empty($this->repositoryUrl)) {
            throw new DoNotCareException('tag, branch or repository url are empty');
        }
    }

    /**
     * Find branch or tag name
     *
     * @param string $ref
     * @return string
     * @throws DoNotCareException
     */
    private function getTagOrBranchFromRef(string $ref): string
    {
        if (str_starts_with($ref, 'refs/tags/')) {
            return str_replace('refs/tags/', '', $ref);
        }
        if (str_starts_with($ref, 'refs/heads/')) {
            return str_replace('refs/heads/', '', $ref);
        }
        throw new DoNotCareException('no tags, no heads in ref');
    }

    /**
     * @param string $repositoryUrl
     * @return string
     */
    private function extractRepositoryNameFromUrl(string $repositoryUrl): string
    {
        // Extract repository name from URL
        $path = trim(parse_url($repositoryUrl, PHP_URL_PATH), '/');

        // Remove .git suffix
        $path = substr($path, 0, -4);

        return $path ?: '';
    }
}
