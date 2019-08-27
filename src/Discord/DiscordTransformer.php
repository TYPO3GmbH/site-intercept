<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Discord;

use Woeler\DiscordPhp\Message\DiscordEmbedsMessage;

class DiscordTransformer extends AbstractDiscordTransformer
{
    protected function transformPayloadToDiscordMessage(array $payload): DiscordEmbedsMessage
    {
        $message = new DiscordEmbedsMessage();
        if (isset($payload['username'])) {
            $message->setUsername($payload['username']);
        }
        if (isset($payload['avatar_url'])) {
            $message->setAvatar($payload['avatar_url']);
        }
        if (isset($payload['content'])) {
            $message->setContent($payload['content']);
        }
        if (isset($payload['embeds']['title'])) {
            $message->setTitle($payload['embeds']['title']);
        }
        if (isset($payload['embeds']['description'])) {
            $message->setDescription($payload['embeds']['description']);
        }
        if (isset($payload['embeds']['color'])) {
            $message->setColor($payload['embeds']['color']);
        }
        if (isset($payload['embeds']['author'])) {
            if (isset($payload['embeds']['author']['name'])) {
                $message->setAuthorName($payload['embeds']['author']['name']);
            }
            if (isset($payload['embeds']['author']['url'])) {
                $message->setAuthorUrl($payload['embeds']['author']['url']);
            }
            if (isset($payload['embeds']['author']['icon_url'])) {
                $message->setAuthorIcon($payload['embeds']['author']['icon_url']);
            }
        }
        if (isset($payload['embeds']['image'])) {
            if (isset($payload['embeds']['image']['url'])) {
                $message->setImage($payload['embeds']['image']['url']);
            }
        }
        if (isset($payload['embeds']['thumbnail'])) {
            if (isset($payload['embeds']['thumbnail']['url'])) {
                $message->setThumbnail($payload['embeds']['thumbnail']['url']);
            }
        }
        if (isset($payload['embeds']['footer'])) {
            if (isset($payload['embeds']['footer']['text'])) {
                $message->setFooterText($payload['embeds']['footer']['text']);
            }
            if (isset($payload['embeds']['footer']['icon_url'])) {
                $message->setFooterIcon($payload['embeds']['footer']['icon_url']);
            }
        }

        return $message;
    }
}
