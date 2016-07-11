<?php
declare(strict_types = 1);

namespace T3G\Intercept\Gerrit;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use T3G\Intercept\Traits\Logger;

/**
 * Class CurlGerritPostRequest
 *
 * Responsible for all requests sent to Gerrit
 *
 * @codeCoverageIgnore tested via integration tests only
 * @package T3G\Intercept\Requests
 */
class Request
{
    use Logger;

    /**
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $baseUrl = 'https://review.typo3.org/a/';

    /**
     * CurlGerritPostRequest constructor.
     *
     * @param \Psr\Log\LoggerInterface|null $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->setLogger($logger);
        $this->client = new Client(['base_uri' => $this->baseUrl]);
    }

    /**
     * @param string $apiPath
     * @param array $postFields
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function postRequest(string $apiPath, array $postFields) : ResponseInterface
    {

        $this->logger->info(
            'cURL request to url ' . $this->baseUrl . $apiPath . ' with params ' . print_r($postFields, true)
        );

        return $this->client->post(
            $apiPath,
            [
                'headers' => [
                    'authorization' => 'Basic dHlwbzNjb21fYmFtYm9vOjBMZnhjbFVackVRSWhDM2JmZ0lSZTJNUVBnc1I1cEljcWIvZ2dZUy9Kdw==',
                    'cache-control' => 'no-cache',
                    'content-type' => 'application/json'
                ],
                'json' => $postFields
            ]
        );
    }
}