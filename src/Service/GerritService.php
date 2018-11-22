<?php
declare(strict_types = 1);
namespace App\Service;

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use App\Client\GerritClient;
use App\Extractor\BambooBuildStatus;
use App\Utility\TimeUtility;
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
     * @return ResponseInterface
     */
    public function voteOnGerrit(BambooBuildStatus $buildInformation): ResponseInterface
    {
        $apiPath = 'changes/' . $buildInformation->change
            . '/revisions/' . $buildInformation->patchSet
            . '/review';

        $verification = $buildInformation->success ? '+1' : '-1';
        $message = $this->getMessage($buildInformation);

        $postFields = [
            'message' => $message,
            'labels' => [
                'Verified' => $verification
            ]
        ];
        return $this->sendGerritPost($apiPath, $postFields);
    }

    /**
     * Create a readable message to be shown on gerrit
     *
     * @param BambooBuildStatus $buildInformation
     * @return string
     */
    private function getMessage(BambooBuildStatus $buildInformation): string
    {
        $messageParts[] = 'Completed build in '
            . TimeUtility::convertSecondsToHumanReadable($buildInformation->buildDurationInSeconds)
            . ' on ' . $buildInformation->prettyBuildCompletedTime;
        $messageParts[] = 'Test Summary: ' . $buildInformation->testSummary;
        $messageParts[] = 'Find logs and detail information at ' . $buildInformation->buildUrl;
        return implode("\n", $messageParts);
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
