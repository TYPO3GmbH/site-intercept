<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Client\BambooClient;
use App\Extractor\BambooBuildStatus;
use App\Extractor\BambooSlackMessage;
use App\Extractor\GerritToBambooCore;
use App\Extractor\GithubPushEventForDocs;
use Psr\Http\Message\ResponseInterface;

/**
 * Check and prepare requests sent to bamboo
 */
class BambooService
{
    /**
     * @var BambooClient
     */
    private $client;

    /**
     * BambooService constructor.
     *
     * @param BambooClient $client
     */
    public function __construct(BambooClient $client)
    {
        $this->client = $client;
    }

    /**
     * Fetch details of a recent build. Used by bamboo post build controller
     * to create a vote on gerrit based on these details.
     *
     * @param BambooSlackMessage $slackMessage
     * @return BambooBuildStatus
     */
    public function getBuildStatus(BambooSlackMessage $slackMessage): BambooBuildStatus
    {
        $apiPath = 'latest/result/' . $slackMessage->buildKey;
        $apiPathParams = '?os_authType=basic&expand=labels';

        $uri = $apiPath . $apiPathParams;
        $response = $this->client->get($uri, [
            'headers' => [
                'accept' => 'application/json',
                'authorization' => getenv('BAMBOO_AUTHORIZATION'),
                'cache-control' => 'no-cache',
                'content-type' => 'application/json',
                'x-atlassian-token' => 'nocheck'
            ],
        ]);

        return new BambooBuildStatus((string)$response->getBody());
    }

    /**
     * Triggers a new build in one of the bamboo core pre-merge branch projects
     *
     * @param GerritToBambooCore $pushEvent
     * @return ResponseInterface
     */
    public function triggerNewCoreBuild(GerritToBambooCore $pushEvent): ResponseInterface
    {
        $apiPath = 'latest/queue/'
            . $pushEvent->bambooProject . '?'
            . implode('&', [
                'stage=',
                'os_authType=basic',
                'executeAllStages=',
                'bamboo.variable.changeUrl=' . (string)$pushEvent->changeId,
                'bamboo.variable.patchset=' . (string)$pushEvent->patchSet
            ]);
        return $this->sendBambooPost($apiPath);
    }

    /**
     * Triggers new build in project CORE-DR
     *
     * @param GithubPushEventForDocs $pushEventInformation
     * @return ResponseInterface
     */
    public function triggerDocumentationPlan(GithubPushEventForDocs $pushEventInformation): ResponseInterface
    {
        $uri = 'latest/queue/CORE-DR?' . implode('&', [
            'stage=',
            'executeAllStages=',
            'os_authType=basic',
            'bamboo.variable.VERSION_NUMBER=' . urlencode($pushEventInformation->versionNumber),
            'bamboo.variable.REPOSITORY_URL=' . urlencode($pushEventInformation->repositoryUrl),
        ]);
        return $this->sendBambooPost($uri);
    }

    /**
     * Execute http request to bamboo
     *
     * @param string $uri
     * @return ResponseInterface
     */
    private function sendBambooPost(string $uri): ResponseInterface
    {
        return $this->client->post(
            $uri,
            [
                'headers' => [
                    'authorization' => getenv('BAMBOO_AUTHORIZATION'),
                    'cache-control' => 'no-cache',
                    'x-atlassian-token' => 'nocheck'
                ],
            ]
        );
    }
}
