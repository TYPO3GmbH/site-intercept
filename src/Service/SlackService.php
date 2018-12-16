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
use App\Creator\SlackCoreNightlyBuildMessage;
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

    /**
     * Send a nightly build status message to slack
     *
     * @param SlackCoreNightlyBuildMessage $message
     * @return ResponseInterface
     */
    public function sendNightlyBuildMessage(SlackCoreNightlyBuildMessage $message): ResponseInterface
    {
        $response = $this->client->post(
            getenv('SLACK_HOOK'),
            [
                RequestOptions::JSON => $message,
            ]
        );
        return $response;
    }
}
