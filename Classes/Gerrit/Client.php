<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/build-information-service.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Intercept\Gerrit;

use GuzzleHttp\Client as GuzzleClient;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use T3G\Intercept\Traits\Logger;

/**
 * Class CurlGerritPostRequest
 *
 * Responsible for all requests sent to Gerrit
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
        $this->client = new GuzzleClient(['base_uri' => $this->baseUrl]);
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
                    'authorization' => getenv('GERRIT_AUTHORIZATION'),
                    'cache-control' => 'no-cache',
                    'content-type' => 'application/json'
                ],
                'json' => $postFields
            ]
        );
    }
}
