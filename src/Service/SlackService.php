<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Client\SlackClient;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

/**
 * Send slack messages
 */
class SlackService
{
    /**
     * @var SlackClient
     */
    private $client;

    /**
     * SlackService constructor.
     *
     * @param SlackClient $client
     */
    public function __construct(SlackClient $client)
    {
        $this->client = $client;
    }

    public function sendNightlyBuildMessage(): ResponseInterface
    {
        $response = $this->client->post(
            getenv('SLACK_HOOK'),
            [
                RequestOptions::JSON => [
                    'attachments' => [[
                        'author_name' => 'Bamboo Bernd',
                        'color' => '#a30000',
                        'text' => 'Nobody expects it.',
                        'title' => 'The spanish inquisition',
                        //'text' => '<https://bamboo.typo3.com/browse/CORE-GTN95|Core › Core 9.5 nightly › #5> failed. 79319 passed. Scheduled',
                    ]]
                ]
            ]
        );
        return $response;
    }
}
