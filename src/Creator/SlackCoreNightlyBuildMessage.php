<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Creator;

/**
 * A slack message for a core nightly build status message
 */
class SlackCoreNightlyBuildMessage implements \JsonSerializable
{
    public const BUILD_FAILED = 0;
    public const BUILD_SUCCESSFUL = 1;

    /**
     * @var int one of the constants above
     */
    private int $status;

    /**
     * @var string Full plan key with number, eg. 'CORE-GTC-23'
     */
    private string $buildKey;

    /**
     * @var string Project name, eg. 'Core nightly master'
     */
    private string $projectName;

    /**
     * @var string Plan name, eg. 'Core'
     */
    private string $planName;

    /**
     * @var int Build number, eg. '23'
     */
    private int $buildNumber;

    /**
     * Create a readable message to be shown on gerrit.
     *
     * @param int $status
     * @param string $buildKey
     * @param string $projectName
     * @param string $planName
     * @param int $buildNumber
     */
    public function __construct(int $status, string $buildKey, string $projectName, string $planName, int $buildNumber)
    {
        if (!in_array($status, [self::BUILD_FAILED, self::BUILD_SUCCESSFUL], true)) {
            throw new \RuntimeException('Broken status ' . $status);
        }
        $this->status = $status;
        $this->buildKey = $buildKey;
        $this->projectName = $projectName;
        $this->planName = $planName;
        $this->buildNumber = $buildNumber;
    }

    /**
     * Json ready to send to slack
     *
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        $failedOrSuccessful = $this->status === self::BUILD_FAILED ? ' failed.' : ' successful.';
        return [
            'channel' => getenv('SLACK_CHANNEL'),
            'username' => 'Bamboo Bernd',
            'attachments' => [
                [
                    'color' => $this->status === self::BUILD_FAILED ? 'danger' : 'good',
                    'text' => '<https://bamboo.typo3.com/browse/' . $this->buildKey
                        . '|' . $this->projectName
                        . ' › ' . $this->planName
                        . ' › #' . $this->buildNumber . '>'
                        . $failedOrSuccessful,
                    'fallback' => $this->projectName . ' › ' . $this->planName . ' › #' . $this->buildNumber . $failedOrSuccessful,
                ]
            ],
        ];
    }
}
