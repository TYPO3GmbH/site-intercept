<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use stdClass;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class GitRepositoryService
{
    public const SERVICE_BITBUCKET_CLOUD = 'bitbucket-cloud';
    public const SERVICE_BITBUCKET_SERVER = 'bitbucket-server';
    public const SERVICE_GITHUB = 'github';
    public const SERVICE_GITLAB = 'gitlab';

    public const SERVICE_NAMES = [
        self::SERVICE_GITHUB => 'GitHub',
        self::SERVICE_GITLAB => 'GitLab',
        self::SERVICE_BITBUCKET_CLOUD => 'Bitbucket Cloud',
        self::SERVICE_BITBUCKET_SERVER => 'Bitbucket Server'
    ];

    protected array $composerJsonUrlFormat = [
        self::SERVICE_BITBUCKET_CLOUD => '{baseUrl}/{repoName}/raw/{version}/composer.json',
        self::SERVICE_BITBUCKET_SERVER => '{baseUrl}/projects/{project}/repos/{package}/raw/composer.json?at=refs%2F{type}%2F{version}',
        self::SERVICE_GITLAB => '{baseUrl}/raw/{version}/composer.json',
        self::SERVICE_GITHUB => 'https://raw.githubusercontent.com/{repoName}/{version}/composer.json',
    ];

    protected array $allowedBranches = ['master', 'main', 'documentation-draft'];

    public function resolvePublicComposerJsonUrlByPayload(stdClass $payload, string $repoService): string
    {
        switch ($repoService) {
            case self::SERVICE_BITBUCKET_SERVER:
            case self::SERVICE_BITBUCKET_CLOUD:
                return $this->getPublicComposerUrlForBitbucket($payload);
            case self::SERVICE_GITHUB:
                return $this->getPublicComposerUrlForGithub($payload);
            case self::SERVICE_GITLAB:
                return $this->getPublicComposerUrlForGitlab($payload);
            default:
                return '';
        }
    }

    public function resolvePublicComposerJsonUrl(string $repoService, array $parameters): string
    {
        return $this->getParsedUrl($this->composerJsonUrlFormat[$repoService], $parameters);
    }

    public function getBranchesFromRepositoryUrl(string $repositoryUrl): array
    {
        $output = $this->runGitProcess($repositoryUrl);
        $branchesAndTags = [];
        $regEx = '/([a-z0-9]{40}[\s]*refs\/(heads|tags)\/)(.*)/m';
        foreach (explode(chr(10), $output) as $row) {
            preg_match_all($regEx, $row, $matches, PREG_SET_ORDER, 0);
            if (!empty($matches[0][3])) {
                $branchesAndTags[] = $matches[0][3];
            }
        }

        return $this->filterAllowedBranches($branchesAndTags);
    }

    public function filterAllowedBranches(array $branchesAndTags): array
    {
        $results = [];
        $versions = [];
        foreach ($branchesAndTags as $item) {
            if (in_array($item, $this->allowedBranches, true)) {
                $results[$item] = $item;
            }
            if (preg_match('/^v?(\d+.\d+.\d+)$/', $item)) {
                $versions[] = $item;
            }
        }
        $uniqueVersion = [];

        // Trims down the displayed version of a branch/tag
        foreach ($versions as $version) {
            $versionParts = explode('.', ltrim($version, 'v'));
            $shortVersion = $versionParts[0] . '.' . $versionParts[1];
            if (!isset($uniqueVersion[$shortVersion]) || version_compare(ltrim($version, 'v'), ltrim($uniqueVersion[$shortVersion], 'v')) > 0) {
                $uniqueVersion[$shortVersion] = $version;
            }
        }

        $uniqueVersion = array_flip($uniqueVersion);
        asort($uniqueVersion);

        // Keep main branch on top, then list tags
        return array_merge($results, $uniqueVersion);
    }

    protected function runGitProcess(string $repositoryUrl): string
    {
        $process = new Process(['git', 'ls-remote', '-h', '-t', $repositoryUrl]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        return $process->getOutput();
    }

    protected function getPublicComposerUrlForBitbucket(stdClass $payload): string
    {
        if (isset($payload->repository->links->html->href, $payload->repository->full_name, $payload->push->changes[0]->new->name)) {
            return $this->getParsedUrl($this->composerJsonUrlFormat[self::SERVICE_BITBUCKET_CLOUD], [
                '{baseUrl}' => 'https://bitbucket.org',
                '{repoName}' => (string)$payload->repository->full_name,
                '{version}' => (string)$payload->push->changes[0]->new->name,
            ]);
        }

        $tag = false;
        if (preg_match('/^v?(\d+.\d+.\d+)$/', (string)$payload->changes[0]->ref->displayId)) {
            $tag = true;
        }

        return $this->getParsedUrl($this->composerJsonUrlFormat[self::SERVICE_BITBUCKET_SERVER], [
            '{baseUrl}' => 'https://' . explode('/', str_replace('https://', '', (string)$payload->repository->links->self[0]->href))[0],
            '{package}' => (string)$payload->repository->name,
            '{project}' => (string)$payload->repository->project->key,
            '{version}' => (string)$payload->changes[0]->ref->displayId,
            '{type}' => $tag ? 'tags' : 'heads',
        ]);
    }

    protected function getPublicComposerUrlForGitlab(stdClass $payload): string
    {
        return $this->getParsedUrl($this->composerJsonUrlFormat[self::SERVICE_GITLAB], [
            '{baseUrl}' => (string)$payload->project->web_url,
            '{version}' => str_replace(['refs/tags/', 'refs/heads/'], '', (string)$payload->ref),
        ]);
    }

    protected function getPublicComposerUrlForGithub(stdClass $payload): string
    {
        $version = str_replace(['refs/tags/', 'refs/heads/'], '', (string)$payload->ref);

        return $this->getParsedUrl($this->composerJsonUrlFormat[self::SERVICE_GITHUB], [
            '{repoName}' => (string)$payload->repository->full_name,
            '{version}' => $version,
        ]);
    }

    protected function getParsedUrl(string $format, array $parameters): string
    {
        return str_replace(array_keys($parameters), $parameters, $format);
    }
}
