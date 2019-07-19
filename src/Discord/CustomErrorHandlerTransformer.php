<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Discord;

use App\Entity\DiscordWebhook;
use Woeler\DiscordPhp\Message\DiscordEmbedsMessage;

class CustomErrorHandlerTransformer extends AbstractDiscordTransformer
{
    private const LOG_COLORS = [
        0 => '8B0000',
        1 => 'FF0000',
        2 => 'B22222',
        3 => 'DC143C',
        4 => 'FFD700',
        5 => '40E0D0',
        6 => 'FFFFFF',
        7 => '000000',
    ];

    /**
     * @param array $payload
     * @return DiscordEmbedsMessage
     */
    protected function transformPayloadToDiscordMessage(array $payload): DiscordEmbedsMessage
    {
        $message = (new DiscordEmbedsMessage())
            ->setDescription('```' . $payload['message'] . '```')
            ->setTitle('Log entry for ' . $payload['project_name'])
            ->setColorWithHexValue(self::LOG_COLORS[$payload['log_level']])
            ->addField('Log Level', $payload['log_level'], true);

        return $message;
    }

    /**
     * @param array $payload
     * @param DiscordWebhook $discordWebhook
     * @return bool
     */
    public function shouldBeSent(array $payload, DiscordWebhook $discordWebhook): bool
    {
        return $payload['log_level'] <= $discordWebhook->getLogLevel();
    }
}
