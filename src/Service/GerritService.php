<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Client\GerritClient;
use App\Creator\GerritBuildStatusMessage;
use App\Extractor\BambooBuildStatus;
use Psr\Http\Message\ResponseInterface;

/**
 * Post a vote with build information on gerrit.
 */
class GerritService
{
    /**
     * @var GerritClient
     */
    private $client;

    /**
     * GerritService constructor.
     *
     * @param GerritClient $client
     */
    public function __construct(GerritClient $client)
    {
        $this->client = $client;
    }

    /**
     * Create a vote and message on gerrit after a bamboo build
     * finished that has been triggered by a gerrit push.
     *
     * @param BambooBuildStatus $buildInformation
     * @param GerritBuildStatusMessage $message
     * @return ResponseInterface
     */
    public function voteOnGerrit(BambooBuildStatus $buildInformation, GerritBuildStatusMessage $message): ResponseInterface
    {
        $apiPath = 'changes/' . $buildInformation->change
            . '/revisions/' . $buildInformation->patchSet
            . '/review';

        $verification = $buildInformation->success ? '+1' : '-1';

        $postFields = [
            'message' => $message->message,
            'labels' => [
                'Verified' => $verification
            ]
        ];
        return $this->sendGerritPost($apiPath, $postFields);
    }

    /**
     * Executed http request to gerrit
     *
     * @param string $apiPath
     * @param array $postFields
     * @return ResponseInterface
     */
    private function sendGerritPost(string $apiPath, array $postFields): ResponseInterface
    {
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
