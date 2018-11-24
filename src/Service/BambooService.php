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
use App\Extractor\GerritPushEvent;
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
     * @var array Map gerrit branches to bamboo plan keys
     */
    private $branchToProjectKey = [
        'master' => 'CORE-GTC',
        'master-testbed-lolli' => 'CORE-TL',
        'TYPO3_8-7' => 'CORE-GTC87',
        'TYPO3_7-6' => 'CORE-GTC76'
    ];

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
     * @param string $buildKey Project-Plan-BuildNumber, eg. CORE-GTC-30244
     * @return ResponseInterface
     */
    public function getBuildStatus(string $buildKey): ResponseInterface
    {
        $apiPath = 'latest/result/' . $buildKey;
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
            'verify' => false
        ]);

        return $response;
    }

    /**
     * Triggers a new build in one of the bamboo core pre-merge branch projects
     *
     * @param GerritPushEvent $pushEvent
     * @return ResponseInterface
     */
    public function triggerNewCoreBuild(GerritPushEvent $pushEvent): ResponseInterface
    {
        $branch = $pushEvent->branch;
        if (!array_key_exists($branch, $this->branchToProjectKey)) {
            throw new \InvalidArgumentException(
                'Branch ' . $branch . ' does not point to a bamboo project name',
                1472210110
            );
        }

        $apiPath = 'latest/queue/' . (string)$this->branchToProjectKey[$branch];
        $apiPathParams = '?stage='
            . '&os_authType=basic'
            . '&executeAllStages=&'
            . 'bamboo.variable.changeUrl=' . urlencode($pushEvent->changeUrl)
            . '&bamboo.variable.patchset=' . (string)$pushEvent->patchSet;
        $uri = $apiPath . $apiPathParams;

        return $this->sendBambooPost($uri);
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
                'verify' => false
            ]
        );
    }

    /**
     * @param array $branchToProjectKey Mapping information
     * @internal For testing
     */
    public function setBranchToProjectKey(array $branchToProjectKey)
    {
        $this->branchToProjectKey = $branchToProjectKey;
    }
}
