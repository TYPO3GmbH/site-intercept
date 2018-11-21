<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/build-information-service.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service\Bamboo;

use GuzzleHttp\Client as GuzzleClient;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use T3G\Intercept\Github\DocumentationRenderingRequest;

/**
 * Responsible for all requests sent to bamboo
 * @codeCoverageIgnore tested via integration tests only
 */
class BambooClientService
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * @var string Main bamboo rst api url
     */
    protected $baseUrl = 'https://bamboo.typo3.com/rest/api/';

    /**
     * @var array Map gerrit branches to bamboo plan keys
     */
    protected $branchToProjectKey = [
        'master' => 'CORE-GTC',
        'master-testbed-lolli' => 'CORE-TL',
        'TYPO3_8-7' => 'CORE-GTC87',
        'TYPO3_7-6' => 'CORE-GTC76'
    ];

    public function __construct(LoggerInterface $logger, GuzzleClient $client)
    {
        $this->logger = $logger;
        $this->client = $client ?: new GuzzleClient(['base_uri' => $this->baseUrl]);
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
     * Triggers new build in project CORE-GTC
     *
     * @param string $changeUrl
     * @param int $patchset
     * @param string $branch Branch name, eg. "master" or "TYPO3_7-6"
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function triggerNewCoreBuild(string $changeUrl, int $patchset, string $branch)
    {
        if (!array_key_exists($branch, $this->branchToProjectKey)) {
            throw new \InvalidArgumentException(
                'Branch ' . $branch . ' does not point to a bamboo project name',
                1472210110
            );
        }

        $apiPath = 'latest/queue/' . (string)$this->branchToProjectKey[$branch];
        $apiPathParams = '?stage=&os_authType=basic&executeAllStages=&bamboo.variable.changeUrl=' .
            urlencode($changeUrl) . '&bamboo.variable.patchset=' . $patchset;
        $uri = $apiPath . $apiPathParams;

        $this->logger->info('cURL request to url ' . $this->baseUrl);
        return $this->sendBambooPost($uri);
    }

    /**
     * Triggers new build in project CORE-DR
     */
    public function triggerDocumentationPlan(
        DocumentationRenderingRequest $documentationRenderingRequest
    ): ResponseInterface {
        $uri = 'latest/queue/CORE-DR?' . implode('&', [
            'stage=',
            'executeAllStages=',
            'os_authType=basic',
            'bamboo.variable.VERSION_NUMBER=' . urlencode($documentationRenderingRequest->getVersionNumber()),
            'bamboo.variable.REPOSITORY_URL=' . urlencode($documentationRenderingRequest->getRepositoryUrl()),
        ]);

        $this->logger->info('cURL request to url ' . $this->baseUrl);
        return $this->sendBambooPost($uri);
    }

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
