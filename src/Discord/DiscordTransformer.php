<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Discord;

use Woeler\DiscordPhp\Message\AbstractDiscordMessage;
use Woeler\DiscordPhp\Message\DiscordEmbedsMessage;
use Woeler\DiscordPhp\Message\DiscordTextMessage;

class DiscordTransformer extends AbstractDiscordTransformer
{
    protected function transformPayloadToDiscordMessage(array $payload): AbstractDiscordMessage
    {
        if (isset($payload['embeds'])) {
            $message = new DiscordEmbedsMessage();
            $embed = array_pop($payload['embeds']);
            if (isset($embed['title'])) {
                $message->setTitle($embed['title']);
            }
            if (isset($embed['description'])) {
                $message->setDescription($embed['description']);
            }
            if (isset($embed['color'])) {
                $message->setColor($embed['color']);
            }
            if (isset($embed['author'])) {
                if (isset($embed['author']['name'])) {
                    $message->setAuthorName($embed['author']['name']);
                }
                if (isset($embed['author']['url'])) {
                    $message->setAuthorUrl($embed['author']['url']);
                }
                if (isset($embed['author']['icon_url'])) {
                    $message->setAuthorIcon($embed['author']['icon_url']);
                }
            }
            if (isset($embed['image'])) {
                if (isset($embed['image']['url'])) {
                    $message->setImage($embed['image']['url']);
                }
            }
            if (isset($embed['thumbnail'])) {
                if (isset($embed['thumbnail']['url'])) {
                    $message->setThumbnail($embed['thumbnail']['url']);
                }
            }
            if (isset($embed['footer'])) {
                if (isset($embed['footer']['text'])) {
                    $message->setFooterText($embed['footer']['text']);
                }
                if (isset($embed['footer']['icon_url'])) {
                    $message->setFooterIcon($embed['footer']['icon_url']);
                }
            }
            if (isset($embed['fields'])) {
                foreach ($embed['fields'] as $field) {
                    $message->addField($field['name'], $field['value'], $field['inline'] ?? false);
                }
            }
        } else {
            $message = new DiscordTextMessage();
        }

        if (isset($payload['username'])) {
            $message->setUsername($payload['username']);
        }
        if (isset($payload['avatar_url'])) {
            $message->setAvatar($payload['avatar_url']);
        }
        if (isset($payload['content'])) {
            $message->setContent($payload['content']);
        }

        return $message;
    }
}
