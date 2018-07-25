<?php

/*
 * This file is part of the package t3g/build-information-service.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Intercept\Github;

use GuzzleHttp\Client as GuzzleClient;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use T3G\Intercept\Traits\Logger;

/**
 * Class GithubRequests
 *
 * Responsible for all requests sent to Github
 *
 * @codeCoverageIgnore tested via integration tests only
 */
class Client
{
    use Logger;

    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

    protected $accessKey;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->setLogger($logger);
        $this->client = new GuzzleClient();
        $this->accessKey = getenv('GITHUB_ACCESS_TOKEN');
    }

    public function get(string $url) : ResponseInterface
    {
        $this->logger->info('GET request to: ' . $url);
        return $this->client->get($url);
    }

    public function patch(string $url, array $data) : ResponseInterface
    {
        $this->logger->info('PATCH request to:' . $url);
        $url .= '?access_token=' . $this->accessKey;
        return $this->client->patch($url, ['json' => $data]);
    }

    public function post(string $url, array $data)
    {
        $this->logger->info('POST request to:' . $url);
        $url .= '?access_token=' . $this->accessKey;
        return $this->client->post($url, ['json' => $data]);
    }

    public function put(string $url)
    {
        $this->logger->info('PUT request to:' . $url);
        $url .= '?access_token=' . $this->accessKey;
        return $this->client->put($url);
    }
}
