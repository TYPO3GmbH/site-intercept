<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Strategy\GithubRst;

use App\Client\GeneralClient;
use App\Extractor\GithubPushEventForCore;

class AddedFilesStrategy implements StrategyInterface
{
    private GeneralClient $client;

    public function __construct(GeneralClient $client)
    {
        $this->client = $client;
    }

    public function match(string $type): bool
    {
        return $type === 'added';
    }

    public function getFromGithub(GithubPushEventForCore $pushEvent, string $filename): string
    {
        $url = sprintf('https://raw.githubusercontent.com/%s/%s/%s', $pushEvent->repositoryFullName, $pushEvent->commit['id'], $filename);
        $response = $this->client->request('GET', $url);

        return (string)$response->getBody();
    }

    public function formatResponse(string $response): string
    {
        return sprintf("```rst\n%s\n```\n", $response);
    }
}
