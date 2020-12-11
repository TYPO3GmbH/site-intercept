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
use App\Extractor\BambooStatus;
use App\Extractor\DeploymentInformation;
use App\Extractor\GerritToBambooCore;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

/**
 * Check and prepare requests sent to bamboo
 */
class BambooService
{
    private BambooClient $client;

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
     * Get a bamboo status: Number of online agents and queue length
     *
     * @return BambooStatus
     */
    public function getBambooStatus(): BambooStatus
    {
        try {
            $agentStatus = $this->sendBamboo('get', 'latest/agent/remote?os_authType=basic', true);
            $queueStatus = $this->sendBamboo('get', 'latest/queue?os_authType=basic');
        } catch (RequestException $e) {
            return new BambooStatus(false);
        }
        return new BambooStatus(true, (string)$agentStatus->getBody(), (string)$queueStatus->getBody());
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
            . http_build_query([
                'stage' => '',
                'os_authType' => 'basic',
                'executeAllStages' => '',
                'bamboo.variable.changeUrl' => $pushEvent->changeId,
                'bamboo.variable.patchset' => $pushEvent->patchSet,
            ]);
        $response = $this->sendBamboo('post', $url);
        return new BambooBuildTriggered((string)$response->getBody());
    }

    /**
     * Triggers a new build in one of the bamboo core pre-merge branch projects
     *
     * @param GerritToBambooCore $pushEvent
     */
    public function stopCoreBuildByChangeId(GerritToBambooCore $pushEvent): void
    {
        try {
            $url = 'latest/result?'
                   . http_build_query([
                    'includeAllStates' => 'true',
                    'buildstate' => 'Unknown',
                    'label' => 'change-' . $pushEvent->changeId
                ]);
            $response = $this->sendBamboo('get', $url);
            $body = (string)$response->getBody();
            if ($body !== '') {
                $response = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                foreach ($response->results->result ?? [] as $result) {
                    if (!in_array($result->state, ['Successful', 'Failed'], true)) {
                        $this->sendBamboo(
                            'delete',
                            'latest/queue/' . $result->buildResultKey
                        );
                    }
                }
            }
        } catch (ClientException $e) {
            // no existing builds found
        }
    }

    /**
     * Triggers a new build in one of the bamboo core branches without a specific patch
     *
     * @param string $bambooProject
     * @return BambooBuildTriggered
     */
    public function triggerNewCoreBuildWithoutPatch(string $bambooProject): BambooBuildTriggered
    {
        $url = 'latest/queue/'
            . $bambooProject . '?'
            . http_build_query([
                'stage' => '',
                'os_authType' => 'basic',
                'executeAllStages' => ''
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
            . $buildKey . '?os_authType=basic';
        $response = $this->sendBamboo('put', $url);
        return new BambooBuildTriggered((string)$response->getBody());
    }

    /**
     * Triggers new build in project CORE-DDEL for deletion action
     *
     * @param DeploymentInformation $deploymentInformation
     * @return BambooBuildTriggered
     */
    public function triggerDocumentationDeletionPlan(DeploymentInformation $deploymentInformation): BambooBuildTriggered
    {
        $uri = 'latest/queue/CORE-DDEL?'
            . http_build_query([
                'stage' => '',
                'executeAllStages' => '',
                'os_authType' => 'basic',
                'bamboo.variable.BUILD_INFORMATION_FILE' => $deploymentInformation->relativeDumpFile,
            ]);
        $response = $this->sendBamboo('post', $uri);
        return new BambooBuildTriggered((string)$response->getBody());
    }

    /**
     * Triggers new build in project CORE-DR
     *
     * @param DeploymentInformation $deploymentInformation
     * @return BambooBuildTriggered
     */
    public function triggerDocumentationPlan(DeploymentInformation $deploymentInformation): BambooBuildTriggered
    {
        $uri = 'latest/queue/CORE-DR?'
            . http_build_query([
                'stage' => '',
                'executeAllStages' => '',
                'os_authType' => 'basic',
                'bamboo.variable.BUILD_INFORMATION_FILE' => $deploymentInformation->relativeDumpFile,
                'bamboo.variable.PACKAGE' => $deploymentInformation->packageName,
                'bamboo.variable.DIRECTORY' => $deploymentInformation->targetBranchDirectory,
            ]);
        $response = $this->sendBamboo('post', $uri);
        return new BambooBuildTriggered((string)$response->getBody());
    }

    /**
     * Trigger new build of project CORE-DRF: Documentation rendering fluid view helper reference
     *
     * @return BambooBuildTriggered
     */
    public function triggerDocumentationFluidVhPlan(string $buildPlan): BambooBuildTriggered
    {
        $uri = 'latest/queue/CORE-' . $buildPlan . '?'
            . http_build_query([
                'stage' => '',
                'executeAllStages' => '',
                'os_authType' => 'basic',
            ]);
        $response = $this->sendBamboo('post', $uri);
        return new BambooBuildTriggered((string)$response->getBody());
    }

    /**
     * Trigger new build of project CORE-DRS: Documentation rendering Surf 2.0 reference
     *
     * @return BambooBuildTriggered
     */
    public function triggerDocumentationSurf20Plan(): BambooBuildTriggered
    {
        $uri = 'latest/queue/CORE-DRS?'
            . http_build_query([
                'stage' => '',
                'executeAllStages' => '',
                'os_authType' => 'basic',
            ]);
        $response = $this->sendBamboo('post', $uri);
        return new BambooBuildTriggered((string)$response->getBody());
    }

    /**
     * Trigger new build of project CORE-DRSM: Documentation rendering Surf master reference
     *
     * @return BambooBuildTriggered
     */
    public function triggerDocumentationSurfMasterPlan(): BambooBuildTriggered
    {
        $uri = 'latest/queue/CORE-DRSM?'
            . http_build_query([
                'stage' => '',
                'executeAllStages' => '',
                'os_authType' => 'basic',
            ]);
        $response = $this->sendBamboo('post', $uri);
        return new BambooBuildTriggered((string)$response->getBody());
    }

    /**
     * Trigger new build of project CORE-DRF: Documentation rendering fluid view helper reference
     *
     * @return BambooBuildTriggered
     */
    public function triggerDocumentationRedirectsPlan(): BambooBuildTriggered
    {
        $uri = 'latest/queue/CORE-DRD?'
            . http_build_query([
                'stage' => '',
                'executeAllStages' => '',
                'os_authType' => 'basic',
            ]);
        $response = $this->sendBamboo('post', $uri);
        return new BambooBuildTriggered((string)$response->getBody());
    }

    /**
     * This plan is triggered if patches are merged into the main docs
     * homepage repository at https://github.com/TYPO3-Documentation/DocsTypo3Org-Homepage
     *
     * The plan extracts the static web document root resources for the
     * docs.typo3.org live server and deploys them.
     *
     * @return BambooBuildTriggered
     */
    public function triggerDocmuntationServerWebrootResourcesPlan(): BambooBuildTriggered
    {
        $uri = 'latest/queue/CORE-DWR?'
            . http_build_query([
                'stage' => '',
                'executeAllStages' => '',
                'os_authType' => 'basic',
            ]);
        $response = $this->sendBamboo('post', $uri);
        return new BambooBuildTriggered((string)$response->getBody());
    }

    /**
     * Execute http request to bamboo.
     *
     * Argument $elevated needs to be set to true if a "system" related bamboo call is executed
     * like fetching list of remote agents. This is *not* needed for casual build triggers and similar.
     *
     * @param string $method
     * @param string $uri
     * @param bool $elevated If true, the "elevated" user credentials of .env BAMBOO_ELEVATED_AUTHORIZATION are used
     * @return ResponseInterface
     */
    private function sendBamboo(string $method, string $uri, bool $elevated = false): ResponseInterface
    {
        return $this->client->$method(
            $uri,
            [
                'headers' => [
                    'accept' => 'application/json',
                    'authorization' => $elevated ? getenv('BAMBOO_ELEVATED_AUTHORIZATION') : getenv('BAMBOO_AUTHORIZATION'),
                    'cache-control' => 'no-cache',
                    'content-type' => 'application/json',
                    'x-atlassian-token' => 'nocheck'
                ],
            ]
        );
    }
}
