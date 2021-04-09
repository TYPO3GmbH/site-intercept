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
use App\Exception\DoNotCareException;
use App\Extractor\BambooBuildStatus;
use App\Extractor\GerritToBambooCore;
use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Post a vote with build information on gerrit.
 */
class GerritService
{
    private GerritClient $client;

    private string $token;

    /**
     * GerritService constructor.
     *
     * @param GerritClient $client
     * @param string $gerritToken
     */
    public function __construct(GerritClient $client, string $gerritToken)
    {
        $this->client = $client;
        $this->token = $gerritToken;
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
        $apiPath = 'a/changes/' . $buildInformation->change
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
     * Get details of a change from gerrit to determine branch and patch set.
     *
     * @param int $changeId
     * @param int|null $patchSet
     * @return GerritToBambooCore
     * @throws DoNotCareException
     */
    public function getChangeDetails(int $changeId, ?int $patchSet): GerritToBambooCore
    {
        if (empty($patchSet)) {
            // Fetch latest revision only
            $apiPath = 'a/changes/' . $changeId . '?o=CURRENT_REVISION';
        } else {
            // Get all revisions to verify the given patch set actually exists
            $apiPath = 'a/changes/' . $changeId . '?o=ALL_REVISIONS';
        }
        try {
            $response = $this->sendGerritGet($apiPath);
        } catch (ClientException $e) {
            throw new DoNotCareException('Usually: 404, No such change ...');
        }
        // gerrit responses prefix their json with ")]}'\n" which has to be removed first
        $json = json_decode(str_replace(")]}'\n", '', (string)$response->getBody()), true, 512, JSON_THROW_ON_ERROR);
        $project = $json['project'];
        if ($project !== 'Packages/TYPO3.CMS' && $project !== 'Teams/Security/TYPO3v4-Core') {
            throw new DoNotCareException('no interesting project, next please...');
        }
        $branch = $json['branch'];
        if (empty($patchSet)) {
            // Latest patch set number
            $patchSet = (int)$json['revisions'][$json['current_revision']]['_number'];
        } else {
            $found = false;
            foreach ($json['revisions'] as $revision) {
                if ((int)$revision['_number'] === $patchSet) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                throw new DoNotCareException('simply not found');
            }
        }
        return new GerritToBambooCore((string)$changeId, $patchSet, $branch, $project);
    }

    /**
     * Checks if a request from Gerrit has the correct token
     *
     * @param Request $request
     * @return bool
     */
    public function requestIsAuthorized(Request $request): bool
    {
        return $this->token === $request->get('token');
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
                    'authorization' => $_ENV['GERRIT_AUTHORIZATION'] ?? '',
                    'cache-control' => 'no-cache',
                    'content-type' => 'application/json'
                ],
                'json' => $postFields
            ]
        );
    }

    /**
     * Execute http GET request to gerrit
     *
     * @param string $apiPath
     * @return ResponseInterface
     */
    private function sendGerritGet(string $apiPath): ResponseInterface
    {
        return $this->client->get(
            $apiPath,
            [
                'headers' => [
                    'authorization' => $_ENV['GERRIT_AUTHORIZATION'] ?? '',
                    'cache-control' => 'no-cache',
                    'content-type' => 'application/json'
                ],
            ]
        );
    }
}
