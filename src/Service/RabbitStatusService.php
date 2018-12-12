<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Client\RabbitManagementClient;
use App\Extractor\RabbitQueueStatus;
use GuzzleHttp\Exception\ClientException;

/**
 * Service class to retrieve various rabbit mq stats and data
 * via rabbit http management api.
 */
class RabbitStatusService
{
    /**
     * @var RabbitManagementClient
     */
    private $client;

    /**
     * @param RabbitManagementClient $client
     */
    public function __construct(RabbitManagementClient $client)
    {
        $this->client = $client;
    }

    /**
     * Get status details
     *
     * @return RabbitQueueStatus
     */
    public function getStatus(): RabbitQueueStatus
    {
        try {
            $response = $this->client->get(
                'api/queues/%2f/' . getenv('RABBITMQ_SPLIT_QUEUE'),
                [
                    'auth' => [getenv('RABBITMQ_USER'), getenv('RABBITMQ_PASSWORD')]
                ]
            );
            $body = json_decode((string)$response->getBody(), true);
        } catch (ClientException $e) {
            $body = [];
        }
        return new RabbitQueueStatus($body);
    }
}
