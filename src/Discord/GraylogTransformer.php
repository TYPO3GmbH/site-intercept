<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Discord;

use Woeler\DiscordPhp\Message\DiscordEmbedsMessage;

class GraylogTransformer extends AbstractDiscordTransformer
{
    /**
     * @param array $payload
     * @return DiscordEmbedsMessage
     */
    protected function transformPayloadToDiscordMessage(array $payload): DiscordEmbedsMessage
    {
        $message = (new DiscordEmbedsMessage())
            ->setTitle('Stream had 1 messages in the last 5 minutes with trigger condition more than 0 messages. (Current grace time: 0 minutes)')
            ->setDescription($this->buildDescription($payload))
            ->setColorWithHexValue('#ff0000')
            ->setFooterText('TYPO3 Webhook | Graylog');
        $message->setUsername('Graylog');
        $message->setContent('**Alert for Graylog stream ' . $payload['stream']['title'] . '**:');

        return $message;
    }

    private function buildDescription(array $payload): string
    {
        $description = '**Alert Description**: ' . $payload['check_result']['result_description'] . PHP_EOL;
        $description .= '**Date**: ' . $payload['check_result']['triggered_at'] . PHP_EOL;
        $description .= '**Stream ID**: ' . $payload['stream']['id'] . PHP_EOL;
        $description .= '**Stream Title**: ' . $payload['stream']['title'] . PHP_EOL;
        $description .= '**Stream Description**: ' . $payload['stream']['description'] . PHP_EOL;
        $description .= '**Alert Condition**: ' . $payload['stream']['alert_conditions'][0]['title'];

        return $description;
    }
}
