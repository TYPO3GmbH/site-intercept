<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Strategy\GithubRst;

use App\Extractor\GithubPushEventForCore;
use GuzzleHttp\ClientInterface;

readonly class ModifiedFilesStrategy implements StrategyInterface
{
    public function __construct(private ClientInterface $generalClient)
    {
    }

    public function match(string $type): bool
    {
        return 'modified' === $type;
    }

    public function getFromGithub(GithubPushEventForCore $pushEvent, string $filename): string
    {
        $url = sprintf('https://api.github.com/repos/%s/compare/%s...%s', $pushEvent->repositoryFullName, $pushEvent->beforeCommitId, $pushEvent->afterCommitId);
        $response = $this->generalClient->request('GET', $url);
        $compare = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $files = $compare['files'] ?? [];

        foreach ($files as $file) {
            if ($file['filename'] === $filename) {
                return $file['patch'];
            }
        }

        return '';
    }

    public function formatResponse(string $response): string
    {
        return sprintf("```diff\n%s\n```\n", $response);
    }
}
