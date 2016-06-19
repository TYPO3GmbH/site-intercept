<?php
declare(strict_types = 1);

namespace T3G\Intercept;

class SlackMessageParser
{
    /**
     * @return string
     */
    public function parseMessage() : string
    {
        if (
            !empty($_POST['payload']) &&
            preg_match('/<https:\/\/bamboo\.typo3\.com\/browse\/(?<buildKey>.*?)\|/', $_POST['payload'], $matches)
        ) {
            return $matches['buildKey'];
        }
    }
}
