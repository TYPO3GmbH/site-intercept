<?php

namespace T3G\Intercept\Library;


use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use T3G\Intercept\Traits\Logger;

/**
 * Class GithubRequests
 *
 * Responsible for all requests sent to Github
 *
 * @codeCoverageIgnore tested via integration tests only
 * @package T3G\Intercept\Library
 */
class GithubRequests
{
    use Logger;

    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->setLogger($logger);
        $this->client = new Client();
    }

    public function get(string $url) : ResponseInterface
    {
        $this->logger->info('GET request to: ' . $url);
        return $this->client->get($url);
    }


}