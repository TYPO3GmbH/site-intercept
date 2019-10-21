<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Extractor;

/**
 * Extract details from bamboo build status. Used by Bamboo post build
 * controller to create a vote on gerrit from these details.
 */
class BambooBuildStatus
{
    /**
     * @var int The gerrit change number, eg. '12345'
     */
    public $change;

    /**
     * @var int The gerrit patch set number, eg. '5'
     */
    public $patchSet;

    /**
     * @var string build key, eg. 'CORE-GTC-30244'
     */
    public $buildKey;

    /**
     * @var string build url, eg. 'https://bamboo.typo3.com/browse/CORE-GTC-30244
     */
    public $buildUrl;

    /**
     * @var bool True if build was green
     */
    public $success;

    /**
     * @var string Test summary, eg. '6 passed'
     */
    public $testSummary;

    /**
     * @var string Formatted completed time, eg. 'Sat, 18 Jun, 06:59 PM'
     */
    public $prettyBuildCompletedTime;

    /**
     * @var int Build duration in seconds, eg. '123'
     */
    public $buildDurationInSeconds;

    /**
     * @var string Plan name, eg. 'Core master nightly'
     */
    public $planName;

    /**
     * @var string Project name, eg. 'Core'
     */
    public $projectName;

    /**
     * @var int Build number, eg. '42'
     */
    public $buildNumber;

    /**
     * Extract information from a bamboo build status
     *
     * @param string $payload
     */
    public function __construct(string $payload)
    {
        $response = json_decode($payload, true);
        foreach ($response['labels']['label'] ?? [] as $label) {
            // A hack to cope with a bamboo hack which prefixes
            // or suffixes keys with underscore '_'
            $keyValue = trim($label['name'], '_'); // patchset-5 or change-12345
            $keyValue = explode('-', $keyValue);
            if ($keyValue[0] === 'change') {
                $this->change = (int)$keyValue[1];
            }
            if ($keyValue[0] === 'patchset') {
                $this->patchSet = (int)$keyValue[1];
            }
        }
        $this->buildKey = $response['buildResultKey'] ?? null;
        $this->buildUrl = 'https://bamboo.typo3.com/browse/' . $response['buildResultKey'] ?? '';
        $this->success = (bool)$response['successful'];
        $this->testSummary = $response['buildTestSummary'] ?? '';
        $this->prettyBuildCompletedTime = $response['prettyBuildCompletedTime'];
        $this->buildDurationInSeconds = (int)$response['buildDurationInSeconds'];
        $this->planName = $response['planName'] ?? null;
        $this->projectName = $response['projectName'] ?? null;
        $this->buildNumber = $response['buildNumber'] ?? null;
    }
}
