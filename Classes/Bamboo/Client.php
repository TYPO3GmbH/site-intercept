<?php
declare(strict_types = 1);

namespace T3G\Intercept\Bamboo;

use GuzzleHttp\Client as GuzzleClient;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use T3G\Intercept\Traits\Logger;

/**
 * Class CurlBambooRequests
 *
 * Responsible for all requests sent to bamboo
 *
 * @codeCoverageIgnore tested via integration tests only
 * @package T3G\Intercept\Requests
 */
class Client
{
    use Logger;

    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;
    protected $baseUrl = 'https://bamboo.typo3.com/rest/api/';
    protected $projectKey = 'CORE-GTC';

    public function __construct(LoggerInterface $logger = null)
    {
        $this->setLogger($logger);
        $this->client = new GuzzleClient(['base_uri' => $this->baseUrl]);
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
        $response = $this->client->get($uri, ['headers' => [
            'accept' => 'application/json',
            'authorization' => getenv('BAMBOO_AUTHORIZATION'),
            'cache-control' => 'no-cache',
            'content-type' => 'application/json',
            'x-atlassian-token' => 'nocheck'
        ]]);

        return $response;
    }

    /**
     * Triggers new build in project CORE-GTC
     *
     * @param string $changeUrl
     * @param int $patchset
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function triggerNewCoreBuild(string $changeUrl, int $patchset)
    {
        $apiPath = 'latest/queue/' . $this->projectKey;
        $apiPathParams = '?stage=&os_authType=basic&executeAllStages=&bamboo.variable.changeUrl=' .
                         urlencode($changeUrl) . '&bamboo.variable.patchset=' . $patchset;
        $uri = $apiPath . $apiPathParams;

        $this->logger->info('cURL request to url' . $this->baseUrl);

        return $this->client->post($uri, ['headers' => [
                    'authorization' => getenv('BAMBOO_AUTHORIZATION'),
                    'cache-control' => 'no-cache',
                    'x-atlassian-token' => 'nocheck'
                ],
            ]
        );
    }

    /**
     * @param string $projectKey
     * @internal
     */
    public function setProjectKey(string $projectKey)
    {
        $this->projectKey = $projectKey;
    }
}