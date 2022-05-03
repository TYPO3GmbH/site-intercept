<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Utility;

use App\Service\GitRepositoryService;

class RepositoryUrlUtility
{
    /**
     * @param string $repositoryUrl
     * @param string $branch
     * @return string
     */
    private static function extractComposerJsonUrlFromRepositoryUrl(string $repositoryUrl, string $branch): string
    {
        if (strpos($repositoryUrl, 'https://github.com') !== false) {
            return self::extractFromGithub($repositoryUrl, $branch);
        }
        if (strpos($repositoryUrl, 'https://gitlab.com') !== false) {
            return self::extractFromGitlab($repositoryUrl, $branch);
        }
        if (strpos($repositoryUrl, 'https://bitbucket.org') !== false) {
            return self::extractFromBitbucket($repositoryUrl, $branch);
        }
        return '';
    }

    private static function extractFromBitbucket(string $repositoryUrl, string $branch): string
    {
        $repoService = GitRepositoryService::SERVICE_BITBUCKET_CLOUD;
        $packageParts = explode('/', str_replace('https://bitbucket.org/', '', $repositoryUrl));
        $parameters = [
            '{baseUrl}' => 'https://bitbucket.org',
            '{repoName}' => $packageParts[0] . '/' . str_replace('.git', '', $packageParts[1]),
            '{version}' => $branch,
        ];
        return (new GitRepositoryService())->resolvePublicFileUrl('composer.json', $parameters, $repoService);
    }

    private static function extractFromGithub(string $repositoryUrl, string $branch): string
    {
        $repoService = GitRepositoryService::SERVICE_GITHUB;
        $packageParts = explode('/', str_replace(['https://github.com/', '.git'], '', $repositoryUrl));
        $parameters = [
            '{repoName}' => $packageParts[0] . '/' . $packageParts[1],
            '{version}' => $branch,
        ];
        return (new GitRepositoryService())->resolvePublicFileUrl('composer.json', $parameters, $repoService);
    }

    private static function extractFromGitlab(string $repositoryUrl, string $branch): string
    {
        $repoService = GitRepositoryService::SERVICE_GITLAB;
        $parameters = [
            '{baseUrl}' => str_replace('.git', '', $repositoryUrl),
            '{version}' => $branch,
        ];
        return (new GitRepositoryService())->resolvePublicFileUrl('composer.json', $parameters, $repoService);
    }

    private static function extractFromSelfHostedBitBucket(string $repositoryUrl, string $branch): string
    {
        $repoService = GitRepositoryService::SERVICE_BITBUCKET_SERVER;
        $repositoryUrl = str_replace('.git', '', $repositoryUrl);
        $packageParts = explode('/', $repositoryUrl);
        $package = array_pop($packageParts);
        $project = array_pop($packageParts);
        $tag = false;
        if (preg_match('/^v?(\d+.\d+.\d+)$/', $branch)) {
            $tag = true;
        }
        $parameters = [
            '{baseUrl}' => 'https://' . explode('/', str_replace('https://', '', $repositoryUrl))[0],
            '{package}' => $package,
            '{project}' => $project,
            '{version}' => $branch,
            '{type}' => $tag ? 'tags' : 'heads',
        ];
        return (new GitRepositoryService())->resolvePublicFileUrl('composer.json', $parameters, $repoService);
    }

    public static function resolveComposerJsonUrl(string $repositoryUrl, string $branch, string $repositoryType = null): string
    {
        $url = self::extractComposerJsonUrlFromRepositoryUrl($repositoryUrl, $branch);
        if ($url === '' || $repositoryType === GitRepositoryService::SERVICE_BITBUCKET_SERVER) {
            $url = self::extractFromSelfHostedBitBucket($repositoryUrl, $branch);
        }
        return $url;
    }

    public static function extractRepositoryNameFromCloneUrl(string $url): string
    {
        $repositoryNameRegex = '/^.+:(.*)\.git$/';

        if (preg_match($repositoryNameRegex, $url, $matches)) {
            return $matches[1];
        }

        throw new \InvalidArgumentException(sprintf('Cannot extract repository from clone URL %s', $url), 1632320303);
    }
}
