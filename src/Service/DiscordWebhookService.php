<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Client\GeneralClient;
use Woeler\DiscordPhp\Message\AbstractDiscordMessage;

class DiscordWebhookService
{
    protected $client;

    public function __construct(GeneralClient $client)
    {
        $this->client = $client;
    }

    /**
     * @param AbstractDiscordMessage $message
     * @param string $url
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendMessage(AbstractDiscordMessage $message, string $url): void
    {
        $sleep = 0;

        while (null !== $sleep) {
            if ($sleep > 0) {
                usleep($sleep * 1000);
            }
            $response = $this->client->request(
                'POST',
                $url,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'body' => json_encode($message->formatForDiscord()),
                ]
            );

            $result = json_decode($response->getBody(), true);
            if (isset($result['retry_after'])) {
                $sleep = $result['retry_after'];
            } else {
                $sleep = null;
            }
        }
    }
}
