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

class DeletedFilesStrategy implements StrategyInterface
{
    public function __construct(private readonly ClientInterface $generalClient)
    {
    }

    public function match(string $type): bool
    {
        return 'removed' === $type;
    }

    public function getFromGithub(GithubPushEventForCore $pushEvent, string $filename): string
    {
        $url = sprintf('https://raw.githubusercontent.com/%s/%s/%s', $pushEvent->repositoryFullName, $pushEvent->commit['id'], $filename);
        $response = $this->generalClient->request('GET', $url);

        return (string) $response->getBody();
    }

    public function formatResponse(string $response): string
    {
        return sprintf("```rst\n%s\n```\n", $response);
    }
}
