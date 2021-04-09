<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Discord;

use App\Entity\DiscordWebhook;
use Woeler\DiscordPhp\Message\AbstractDiscordMessage;

abstract class AbstractDiscordTransformer
{
    /**
     * @param array $payload
     * @return AbstractDiscordMessage
     */
    public function getDiscordMessage(array $payload): AbstractDiscordMessage
    {
        return $this->transformPayloadToDiscordMessage($payload);
    }

    /**
     * @param array $payload
     * @param DiscordWebhook $discordWebhook
     * @return bool
     */
    public function shouldBeSent(array $payload, DiscordWebhook $discordWebhook): bool
    {
        return true;
    }

    abstract protected function transformPayloadToDiscordMessage(array $payload): AbstractDiscordMessage;
}
