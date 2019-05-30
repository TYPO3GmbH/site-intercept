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
    public const SERVICE_BITBUCKET = 'bitbucket';
    public const SERVICE_BITBUCKET_CLOUD = 'bitbucket-cloud';
    public const SERVICE_BITBUCKET_SERVER = 'bitbucket-server';
    public const SERVICE_GITHUB = 'github';
    public const SERVICE_GITLAB = 'gitlab';

    protected $composerJsonUrlFormat = [
        self::SERVICE_BITBUCKET_CLOUD => '{baseUrl}/raw/{version}/composer.json',
        self::SERVICE_BITBUCKET_SERVER => '{baseUrl}/raw/composer.json?at=refs%2Fheads%2F{version}',
        self::SERVICE_GITLAB => '{baseUrl}/raw/{version}/composer.json',
        self::SERVICE_GITHUB => 'https://raw.githubusercontent.com/{repoName}/{version}/composer.json',
    ];

    protected $allowedBranches = ['master', 'documentation-draft'];

    public function resolvePublicComposerJsonUrlByPayload(stdClass $payload, string $repoService, string $eventType = null): string
    {
        switch ($repoService) {
            case self::SERVICE_BITBUCKET:
            case self::SERVICE_BITBUCKET_CLOUD:
            case self::SERVICE_BITBUCKET_SERVER:
                return $this->getPublicComposerUrlForBitbucket($payload);
                break;
            case self::SERVICE_GITHUB:
                return $this->getPublicComposerUrlForGithub($payload, $eventType);
                break;
            case self::SERVICE_GITLAB:
                return $this->getPublicComposerUrlForGitlab($payload);
                break;
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
        $process = new Process(['git', 'ls-remote', '-h', '-t', $repositoryUrl]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $branchesAndTags = [];
        $regEx = '/([a-z0-9]{40}[\s]*refs\/(heads|tags)\/)(.*)/m';
        foreach (explode(chr(10), $process->getOutput()) as $row) {
            preg_match_all($regEx, $row, $matches, PREG_SET_ORDER, 0);
            if (!empty($matches[0][3])) {
                $branchesAndTags[] = $matches[0][3];
            }
        }
        $results = [];
        $versions = [];
        foreach ($branchesAndTags as $item) {
            if (in_array($item, $this->allowedBranches, true)) {
                $results[$item] = $item;
            }
            $version = ltrim($item, 'v');
            if (preg_match('/^(\d+.\d+.\d+)$/', $version)) {
                $versions[] = $version;
            }
        }
        sort($versions);
        $uniqueVersion = [];
        foreach ($versions as $version) {
            $versionParts = explode('.', $version);
            $shortVersion = $versionParts[0] . '.' . $versionParts[1];
            if (!isset($uniqueVersion[$shortVersion]) || version_compare($version, $uniqueVersion[$shortVersion]) > 0) {
                $uniqueVersion[$shortVersion] = $version;
            }
        }
        return array_merge($results, array_flip($uniqueVersion));
    }

    protected function getPublicComposerUrlForBitbucket(stdClass $payload): string
    {
        if (isset($payload->repository->links->html->href)) {
            return $this->getParsedUrl($this->composerJsonUrlFormat[self::SERVICE_BITBUCKET_CLOUD], [
                '{baseUrl}' => (string)$payload->repository->links->html->href,
                '{repoName}' => (string)$payload->repository->name,
                '{version}' => (string)$payload->push->changes[0]->new->name,
            ]);
        }

        return $this->getParsedUrl($this->composerJsonUrlFormat[self::SERVICE_BITBUCKET_SERVER], [
            '{baseUrl}' => trim(str_replace('browse', '', (string)$payload->repository->links->self[0]->href), '/'),
            '{repoName}' => (string)$payload->repository->name,
            '{version}' => (string)$payload->changes[0]->ref->displayId,
        ]);
    }

    protected function getPublicComposerUrlForGitlab(stdClass $payload): string
    {
        return $this->getParsedUrl($this->composerJsonUrlFormat[self::SERVICE_GITLAB], [
            '{baseUrl}' => (string)$payload->project->web_url,
            '{version}' => str_replace(['refs/tags/', 'refs/heads/'], '', (string)$payload->ref),
        ]);
    }

    protected function getPublicComposerUrlForGithub(stdClass $payload, string $eventType): string
    {
        $version = ($eventType === 'release')
            ? (string)$payload->release->tag_name
            : str_replace(['refs/tags/', 'refs/heads/'], '', (string)$payload->ref);

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
