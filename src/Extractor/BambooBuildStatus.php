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
    public int $change;

    /**
     * @var int The gerrit patch set number, eg. '5'
     */
    public int $patchSet;

    /**
     * @var string build key, eg. 'CORE-GTC-30244'
     */
    public ?string $buildKey = null;

    /**
     * @var string build url, eg. 'https://bamboo.typo3.com/browse/CORE-GTC-30244
     */
    public string $buildUrl;

    /**
     * @var bool True if build was green
     */
    public bool $success;

    /**
     * @var string Test summary, eg. '6 passed'
     */
    public string $testSummary;

    /**
     * @var string Formatted completed time, eg. 'Sat, 18 Jun, 06:59 PM'
     */
    public string $prettyBuildCompletedTime;

    /**
     * @var int Build duration in seconds, eg. '123'
     */
    public int $buildDurationInSeconds;

    /**
     * @var string|null Plan name, eg. 'Core master nightly'
     */
    public ?string $planName;

    /**
     * @var string|null Project name, eg. 'Core'
     */
    public ?string $projectName;

    /**
     * @var int|null Build number, eg. '42'
     */
    public ?int $buildNumber = null;

    /**
     * Extract information from a bamboo build status
     *
     * @param string $payload
     */
    public function __construct(string $payload)
    {
        $response = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
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
