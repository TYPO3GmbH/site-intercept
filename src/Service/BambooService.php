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
use App\Extractor\BambooBuildTriggered;
use App\Extractor\BambooSlackMessage;
use App\Extractor\DocumentationBuildInformation;
use App\Extractor\GerritToBambooCore;
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
        $response = $this->sendBamboo('get', $uri);
        return new BambooBuildStatus((string)$response->getBody());
    }

    /**
     * Triggers a new build in one of the bamboo core pre-merge branch projects
     *
     * @param GerritToBambooCore $pushEvent
     * @return BambooBuildTriggered
     */
    public function triggerNewCoreBuild(GerritToBambooCore $pushEvent): BambooBuildTriggered
    {
        $url = 'latest/queue/'
            . $pushEvent->bambooProject . '?'
            . implode('&', [
                'stage=',
                'os_authType=basic',
                'executeAllStages=',
                'bamboo.variable.changeUrl=' . (string)$pushEvent->changeId,
                'bamboo.variable.patchset=' . (string)$pushEvent->patchSet
            ]);
        $response = $this->sendBamboo('post', $url);
        return new BambooBuildTriggered((string)$response->getBody());
    }

    /**
     * Re-trigger a failed (?) (core nightly?!) build
     *
     * @param string $buildKey, eg. CORE-GTN-4711
     * @return BambooBuildTriggered
     */
    public function reTriggerFailedBuild(string $buildKey): BambooBuildTriggered
    {
        $url = 'latest/queue/'
            . $buildKey . '?'
            . implode('&', [
                'os_authType=basic',
            ]);
        $response = $this->sendBamboo('put', $url);
        return new BambooBuildTriggered((string)$response->getBody());
    }

    /**
     * Triggers new build in project CORE-DR
     *
     * @param DocumentationBuildInformation $documentationBuildInformation
     * @return ResponseInterface
     */
    public function triggerDocumentationPlan(DocumentationBuildInformation $documentationBuildInformation): ResponseInterface
    {
        $uri = 'latest/queue/'
            . 'CORE-DR?'
            . implode('&', [
                'stage=',
                'executeAllStages=',
                'os_authType=basic',
                'bamboo.variable.BUILD_INFORMATION_FILE=' . urlencode($documentationBuildInformation->getFilePath()),
            ]);
        return $this->sendBamboo('post', $uri);
    }

    /**
     * Trigger new build of project CORE-DRF: Documentation rendering fluid view helper reference
     *
     * @return BambooBuildTriggered
     */
    public function triggerDocumentationFluidVhPlan(): BambooBuildTriggered
    {
        $uri = 'latest/queue/'
            . 'CORE-DRF?'
            . implode('&', [
                'stage=',
                'executeAllStages=',
                'os_authType=basic',
            ]);
        $response = $this->sendBamboo('post', $uri);
        return new BambooBuildTriggered((string)$response->getBody());
    }

    /**
     * Trigger new build of project CORE-DRF: Documentation rendering fluid view helper reference
     *
     * @param string|null $filename
     * @return BambooBuildTriggered
     */
    public function triggerDocumentationRedirectsPlan(string $filename = null): BambooBuildTriggered
    {
        $uri = 'latest/queue/CORE-DRD?'
            . http_build_query([
                'stage' => '',
                'executeAllStages' => '',
                'os_authType' => 'basic',
                'bamboo.variable.REDIRECT_FILE' => $filename
            ]);
        $response = $this->sendBamboo('post', $uri);
        return new BambooBuildTriggered((string)$response->getBody());
    }

    /**
     * Execute http request to bamboo
     *
     * @param string $method
     * @param string $uri
     * @return ResponseInterface
     */
    private function sendBamboo(string $method, string $uri): ResponseInterface
    {
        return $this->client->$method(
            $uri,
            [
                'headers' => [
                    'accept' => 'application/json',
                    'authorization' => getenv('BAMBOO_AUTHORIZATION'),
                    'cache-control' => 'no-cache',
                    'content-type' => 'application/json',
                    'x-atlassian-token' => 'nocheck'
                ],
            ]
        );
    }
}
