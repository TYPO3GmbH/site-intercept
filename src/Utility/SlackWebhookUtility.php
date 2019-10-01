<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Utility;

class SlackWebhookUtility
{
    /**
     * Transform Slack markup URLs to Discord markdown URLs
     *
     * @param string $text
     * @return string
     */
    public static function transformUrls(string $text): string
    {
        $matches = [];
        preg_match_all(
            '/<https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_\+.~#?&\/\/=]*)\|[-a-zA-Z0-9@:%._\+~›#=\\ ]{2,256}>/u',
            $text,
            $matches
        );

        foreach ($matches[0] as $match) {
            $transformed = str_replace(['<', '>'], '', $match);
            $parts = explode('|', $transformed);

            $text = str_replace($match, '[' . $parts[1] . '](' . $parts[0] . ')', $text);
        }

        return $text;
    }
}
