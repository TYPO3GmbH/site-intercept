<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Client\GeneralClient;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use Woeler\DiscordPhp\Message\AbstractDiscordMessage;

class DiscordWebhookService
{
    protected GeneralClient $client;

    public function __construct(GeneralClient $client)
    {
        $this->client = $client;
    }

    /**
     * @param AbstractDiscordMessage $message
     * @param string $url
     * @throws GuzzleException
     */
    public function sendMessage(AbstractDiscordMessage $message, string $url): void
    {
        $sleep = 0;

        while (null !== $sleep) {
            if ($sleep > 0) {
                usleep($sleep * 1000);
            }
            try {
                $response = $this->client->request(
                    'POST',
                    $url,
                    [
                        'headers' => [
                            'Content-Type' => 'application/json',
                        ],
                        'body' => json_encode($message->formatForDiscord(), JSON_THROW_ON_ERROR),
                    ]
                );
            } catch (BadResponseException $e) {
                // We don't want to break on 429 too many requests
                if ($e->getResponse()->getStatusCode() !== 429) {
                    throw $e;
                }
            }

            $result = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $sleep = $result['retry_after'] ?? null;
        }
    }
}
