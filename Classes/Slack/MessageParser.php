<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/build-information-service.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Intercept\Slack;

/**
 * Class SlackMessageParser
 *
 * Parses the slack message format send to us via Bamboo Slack Notification hook
 *
 */
class MessageParser
{
    /**
     * @return string
     * @throws \InvalidArgumentException
     */
    public function parseMessage() : string
    {
        if (
            !empty($_POST['payload']) &&
            preg_match('/<https:\/\/bamboo\.typo3\.com\/browse\/(?<buildKey>.*?)\|/', $_POST['payload'], $matches)
        ) {
            return $matches['buildKey'];
        }
        throw new \InvalidArgumentException('SlackMessage could not be parsed.');
    }
}
