<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Entity\DiscordChannel;
use App\Exception\UnexpectedDiscordApiResponseException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;
use Woeler\DiscordPhp\Message\AbstractDiscordMessage;

class DiscordServerService
{
    private string $serverId;

    private string $botToken;

    /**
     * @param string $serverId
     * @param string $botToken
     */
    public function __construct(string $serverId, string $botToken)
    {
        $this->serverId = $serverId;
        $this->botToken = $botToken;
    }

    /**
     * @param string $channelId
     * @param AbstractDiscordMessage $content
     * @throws UnexpectedDiscordApiResponseException
     */
    public function sendMessage(string $channelId, AbstractDiscordMessage $content): void
    {
        $this->request('https://discordapp.com/api/channels/' . $channelId . '/messages', 'POST', $content->formatForDiscord());
    }

    /**
     * @return array
     * @throws UnexpectedDiscordApiResponseException
     */
    public function getChannels(): array
    {
        return $this->request('https://discordapp.com/api/guilds/' . $this->serverId . '/channels');
    }

    /**
     * @param string $channelId
     * @param string $name
     * @param string $avatar
     * @return array
     * @throws UnexpectedDiscordApiResponseException
     */
    public function createWebhook(string $channelId, string $name = 'Intercept', string $avatar = 'https://intercept.typo3.com/build/images/webhookavatars/default.png'): array
    {
        return $this->request('https://discordapp.com/api/channels/' . $channelId . '/webhooks', 'POST', ['name' => $name, 'avatar' => $avatar]);
    }

    /**
     * @param string $channelId
     * @return array
     * @throws UnexpectedDiscordApiResponseException
     */
    public function getWebhooks(string $channelId): array
    {
        return $this->request('https://discordapp.com/api/channels/' . $channelId . '/webhooks');
    }

    /**
     * @param string $channelId
     * @return array
     * @throws UnexpectedDiscordApiResponseException
     */
    public function createOrGetInterceptHook(string $channelId): array
    {
        foreach ($this->getWebhooks($channelId) as $hook) {
            if ($hook['name'] === 'Intercept') {
                return $hook;
            }
        }

        return $this->createWebhook($channelId);
    }

    /**
     * @return array
     * @throws UnexpectedDiscordApiResponseException
     */
    public function getTextChannels(): array
    {
        $channels = $this->getChannels();

        foreach ($channels as $key => $channel) {
            if ($channel['type'] !== DiscordChannel::CHANNEL_TYPE_TEXT) {
                unset($channels[$key]);
            }
        }

        return $channels;
    }

    /**
     * @param string $url
     * @param string $method
     * @param array $payload
     * @return array
     * @throws UnexpectedDiscordApiResponseException
     * @throws GuzzleException
     */
    private function request(string $url, string $method = 'GET', array $payload = []): array
    {
        $client = new Client();
        $response = null;
        $sleep = 0;

        while (null !== $sleep) {
            if ($sleep > 0) {
                usleep($sleep * 1000);
            }
            try {
                if ($method === 'POST') {
                    $response = $client->request(
                        $method,
                        $url,
                        [
                            'headers' => [
                                'Authorization' => 'Bot ' . $this->botToken,
                                'Content-Type' => 'application/json',
                            ],
                            'body' => json_encode($payload, JSON_THROW_ON_ERROR),
                        ]
                    );
                } else {
                    $response = $client->request(
                        $method,
                        $url,
                        [
                            'headers' => [
                                'Authorization' => 'Bot ' . $this->botToken,
                                'Content-Type' => 'application/json',
                            ],
                        ]
                    );
                }
            } catch (BadResponseException $e) {
                // We don't want to break on 429 too many requests
                if ($e->getResponse()->getStatusCode() !== Response::HTTP_TOO_MANY_REQUESTS) {
                    throw new UnexpectedDiscordApiResponseException('Discord API responded with code ' . $e->getResponse()->getStatusCode(), 1561556560);
                }
            }
            if ($response !== null) {
                $result = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            }
            $sleep = $result['retry_after'] ?? null;
        }

        if ($response instanceof ResponseInterface && ($response->getStatusCode() < 200 || $response->getStatusCode() >= 400)) {
            throw new UnexpectedDiscordApiResponseException('Discord API responded with code ' . $response->getStatusCode(), 1561556559);
        }

        return $result ?? [];
    }
}
