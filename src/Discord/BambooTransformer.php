<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Discord;

use App\Utility\SlackWebhookUtility;
use Woeler\DiscordPhp\Message\DiscordEmbedsMessage;

class BambooTransformer extends AbstractDiscordTransformer
{
    /**
     * @param array $payload
     * @return DiscordEmbedsMessage
     */
    protected function transformPayloadToDiscordMessage(array $payload): DiscordEmbedsMessage
    {
        $text = SlackWebhookUtility::transformUrls($payload['attachments'][0]['text']);
        $text = explode('. ', $text);

        if (count($text) > 2) {
            $lastPart = array_pop($text);
            $firstPart = '';

            foreach ($text as $textPart) {
                $firstPart .= $textPart . '. ';
            }

            $text = [0 => trim($firstPart), 1 => $lastPart];
        }

        $message = new DiscordEmbedsMessage();
        $message->setUsername('Bamboo');
        $message->setDescription('**' . $text[0] . '**' . PHP_EOL . '*' . $text[1] . '*');
        $message->setAvatar('https://intercept.typo3.com/build/images/webhookavatars/bamboo.png');
        if ($payload['attachments'][0]['color'] === 'good') {
            $message->setColorWithHexValue('#00FF00');
        } else {
            $message->setColorWithHexValue('#ff0000');
        }
        $details = explode('›', $text[0]);
        $message->setFooterText('TYPO3 Webhook | Bamboo')
            ->addField('Buildplan', trim(ltrim($details[0], '[') . '›' . $details[1]))
            ->addField('Build Identifier', explode(']', trim($details[2]))[0]);

        return $message;
    }
}
