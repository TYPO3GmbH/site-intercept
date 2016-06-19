<?php
declare(strict_types = 1);

namespace T3G\Intercept;

class SlackMessageParser
{
    public function parseMessage()
    {
        if (!empty($_POST['payload'])) {
            if (preg_match('/<https:\/\/bamboo\.typo3\.com\/browse\/(?<buildKey>.*?)\|/', $_POST['payload'], $matches)) {
                return $matches['buildKey'];
            }
        }
    }
}
