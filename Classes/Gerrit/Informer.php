<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/build-information-service.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Intercept\Gerrit;

use T3G\Intercept\Utility\TimeUtility;

/**
 * Class GerritInformer
 *
 * Responsible for:
 * * Posting a vote with build information on Gerrit
 *
 */
class Informer
{
    /**
     * @var Client
     */
    private $requester;

    public function __construct(Client $requester = null)
    {
        $this->requester = $requester ?: new Client();
    }

    /**
     * @param array $buildInformation
     * @return void
     */
    public function voteOnGerrit(array $buildInformation)
    {
        $apiPath = $this->constructApiPath($buildInformation);

        $verification = $buildInformation['success'] ? '+1' : '-1';

        $message = $this->getMessage($buildInformation);
        $postFields = [
            'message' => $message,
            'labels' => [
                'Verified' => $verification
            ]
        ];
        $this->requester->postRequest($apiPath, $postFields);
    }

    private function getMessage(array $buildInformation) : string
    {
        $messageParts[] = 'Completed build in ' .
                          TimeUtility::convertSecondsToHumanReadable($buildInformation['buildDurationInSeconds']) .
                          ' on ' .
                          $buildInformation['prettyBuildCompletedTime'];
        $messageParts[] = 'Test Summary: ' . $buildInformation['buildTestSummary'];
        $messageParts[] = 'Find logs and detail information at ' . $buildInformation['buildUrl'];
        return implode("\n", $messageParts);
    }

    /**
     * @param array $buildInformation
     * @return string
     */
    private function constructApiPath(array $buildInformation) : string
    {
        return 'changes/' . $buildInformation['change'] . '/revisions/' . $buildInformation['patchset'] . '/review';
    }
}
