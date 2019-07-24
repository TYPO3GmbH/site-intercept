<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Discord;

use App\Exception\DiscordTransformerTypeNotFoundException;

final class DiscordTransformerFactory
{
    public const TYPE_BAMBOO = 1;

    public const TYPE_GRAYLOG = 2;

    public const TYPE_CUSTOM_ERROR_HANDLER = 3;

    public const TYPES = [
        self::TYPE_BAMBOO => 'Bamboo',
        self::TYPE_GRAYLOG => 'Graylog',
        self::TYPE_CUSTOM_ERROR_HANDLER => 'Custom error handler',
    ];

    /**
     * @param int $type
     * @return AbstractDiscordTransformer
     * @throws DiscordTransformerTypeNotFoundException
     */
    public static function getTransformer(int $type): AbstractDiscordTransformer
    {
        switch ($type) {
            case self::TYPE_BAMBOO:
                return new BambooTransformer();
            case self::TYPE_GRAYLOG:
                return new GraylogTransformer();
            case self::TYPE_CUSTOM_ERROR_HANDLER:
                return new CustomErrorHandlerTransformer();
            default:
                throw new DiscordTransformerTypeNotFoundException('Transformer class not found for type ' . $type, 1563359524);
        }
    }
}
