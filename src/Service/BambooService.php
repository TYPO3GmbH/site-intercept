<?php
declare(strict_types = 1);
namespace App\Service;

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use App\Client\BambooClient;
use App\Extractor\GithubPushEventDocsInformationExtractor;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Check and prepare requests sent to bamboo
 */
class BambooService
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var \App\Client\BambooClient
     */
    private $client;

    /**
     * @var string Main bamboo rst api url
     */
    private $baseUrl = 'https://bamboo.typo3.com/rest/api/';

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
     * @param LoggerInterface $logger
     * @param BambooClient $client
     */
    public function __construct(LoggerInterface $logger, BambooClient $client)
    {
        $this->logger = $logger;
        $this->client = $client;
    }

    /**
     * @param string $buildKey
     * @return ResponseInterface
     */
    public function getBuildStatus(string $buildKey) : ResponseInterface
    {
        $apiPath = 'latest/result/' . $buildKey;
        $apiPathParams = '?os_authType=basic&expand=labels';

        $uri = $apiPath . $apiPathParams;
        $this->logger->info('cURL request to uri' . $this->baseUrl . $uri);
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
     * @param string $changeUrl
     * @param int $patchSet
     * @param string $branch Branch name, eg. "master" or "TYPO3_7-6"
     * @return ResponseInterface
     */
    public function triggerNewCoreBuild(string $changeUrl, int $patchSet, string $branch): ResponseInterface
    {
        if (!array_key_exists($branch, $this->branchToProjectKey)) {
            throw new \InvalidArgumentException(
                'Branch ' . $branch . ' does not point to a bamboo project name',
                1472210110
            );
        }

        $apiPath = 'latest/queue/' . (string)$this->branchToProjectKey[$branch];
        $apiPathParams = '?stage=&os_authType=basic&executeAllStages=&bamboo.variable.changeUrl=' .
            urlencode($changeUrl) . '&bamboo.variable.patchset=' . $patchSet;
        $uri = $apiPath . $apiPathParams;

        return $this->sendBambooPost($uri);
    }

    /**
     * Triggers new build in project CORE-DR
     *
     * @param GithubPushEventDocsInformationExtractor $pushEventInformation
     * @return ResponseInterface
     */
    public function triggerDocumentationPlan(
        GithubPushEventDocsInformationExtractor $pushEventInformation
    ): ResponseInterface {
        $uri = 'latest/queue/CORE-DR?' . implode('&', [
            'stage=',
            'executeAllStages=',
            'os_authType=basic',
            'bamboo.variable.VERSION_NUMBER=' . urlencode($pushEventInformation->getVersionNumber()),
            'bamboo.variable.REPOSITORY_URL=' . urlencode($pushEventInformation->getRepositoryUrl()),
        ]);
        return $this->sendBambooPost($uri);
    }

    /**
     * Execute http request to bamboo
     *
     * @param string $uri
     * @return ResponseInterface
     */
    protected function sendBambooPost(string $uri): ResponseInterface
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
     * @internal
     */
    public function setBranchToProjectKey(array $branchToProjectKey)
    {
        $this->branchToProjectKey = $branchToProjectKey;
    }
}
