<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Client\PackagistClient;
use App\Extractor\PackagistUpdateRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Update packagist.org repositories
 */
class PackagistService
{
    private PackagistClient $client;
    private LoggerInterface $logger;

    /**
     * @param PackagistClient $client
     * @param LoggerInterface $logger
     */
    public function __construct(PackagistClient $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * Send a nightly build status message to slack
     *
     * @param PackagistUpdateRequest $packagistUpdateRequest
     * @return ResponseInterface
     */
    public function sendUpdateRequest(PackagistUpdateRequest $packagistUpdateRequest): ResponseInterface
    {
        $this->logger->info('Triggered Update of Packagist.org for ' . $packagistUpdateRequest->getRepositoryUrl());
        $response = $this->client->post(
            'https://packagist.org/api/update-package?username=' . $packagistUpdateRequest->getUserName() . '&apiToken=' . $packagistUpdateRequest->getApiToken(),
            [
                'json' => [
                    'repository' => [
                        'url' => $packagistUpdateRequest->getRepositoryUrl(),
                    ],
                ],
            ]
        );
        if ($response->getStatusCode() >= 400) {
            $stream = $response->getBody();
            $stream->rewind();
            $this->logger->error('Packagist update of ' . $packagistUpdateRequest->getRepositoryUrl() . 'failed.', [
                'statusCode' => $response->getStatusCode(),
                'reason' => $response->getReasonPhrase(),
                'body' => $stream->getContents(),
            ]);
        }
        return $response;
    }
}
