<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Client\GraylogClient;
use App\Extractor\GraylogLogEntry;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

/**
 * Get various log messages
 */
class GraylogService
{
    /**
     * @var GraylogClient
     */
    private $client;

    /**
     * SlackService constructor.
     *
     * @param GraylogClient $client
     */
    public function __construct(GraylogClient $client)
    {
        $this->client = $client;
    }

    /**
     * Get a list of graylog bamboo trigger calls
     *
     * @return array
     */
    public function getRecentBambooTriggerLogs(): array
    {
        $query = urlencode(
            'application:intercept AND level:6 AND env:prod AND (ctxt_type:triggerBamboo OR ctxt_type:voteGerrit)'
        );
        try {
            $response = $this->client->get(
                'search/universal/relative'
                . '?query=' . $query
                . '&range=2592000' // 30 days max
                . '&limit=40'
                . '&sort=' . urlencode('timestamp:desc')
                . '&pretty=true',
                [
                    'auth' => [getenv('GRAYLOG_TOKEN'), 'token'],
                ]
            );
            $content = json_decode((string)$response->getBody(), true);
            $messages = [];
            if (isset($content['messages']) && is_array($content['messages'])) {
                foreach ($content['messages'] as $message) {
                    $messages[] = new GraylogLogEntry($message['message']);
                }
            }
            return $messages;
        } catch (ClientException $e) {
            // Silent fail if graylog is broken
            return [];
        } catch (ConnectException $e) {
            // Silent fail if graylog is down
            return [];
        }
    }
}
