<?php
declare(strict_types = 1);
namespace App\Extractor;

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

/**
 * Parses the slack message format send to us via Bamboo Slack Notification hook
 */
class BambooSlackMessage
{
    /**
     * @var string Project-Plan-BuildNumber, eg. 'CORE-GTC-30244'
     */
    public $buildKey;

    /**
     * Extract relevant information from a bamboo created slack message
     *
     * @param string $payload
     * @throws \InvalidArgumentException
     */
    public function __construct(string $payload)
    {
        if (
            !empty($payload) &&
            preg_match('/<https:\/\/bamboo\.typo3\.com\/browse\/(?<buildKey>.*?)\|/', $payload, $matches)
        ) {
            $this->buildKey = $matches['buildKey'];
        } else {
            throw new \InvalidArgumentException('Bamboo slack message could not be parsed.');
        }
    }
}
