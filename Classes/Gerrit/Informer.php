<?php
declare(strict_types = 1);

namespace T3G\Intercept\Gerrit;

use T3G\Intercept\Library\CurlGerritPostRequest;
use T3G\Intercept\Utility\TimeUtility;

/**
 * Class GerritInformer
 *
 * Responsible for:
 * * Posting a vote with build information on Gerrit
 *
 * @package T3G\Intercept
 */
class Informer
{
    /**
     * @var \T3G\Intercept\Library\CurlGerritPostRequest
     */
    private $requester;

    public function __construct(CurlGerritPostRequest $requester = null)
    {
        $this->requester = $requester ?: new CurlGerritPostRequest();
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
        $messageParts[] = "Completed build in " . TimeUtility::convertSecondsToHumanReadable($buildInformation['buildDurationInSeconds']) . ' on '  . $buildInformation['prettyBuildCompletedTime'];
        $messageParts[] = "Test Summary: " . $buildInformation['buildTestSummary'];
        $messageParts[] = "Find logs and detail information at " . $buildInformation['buildUrl'];
        return join("\n", $messageParts);
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