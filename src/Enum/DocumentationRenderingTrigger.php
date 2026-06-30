<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Enum;

enum DocumentationRenderingTrigger: string
{
    case API = 'api';
    case CLI = 'cli';
    case WEB = 'interface';

    public function toHistoryEntryTrigger(): HistoryEntryTrigger
    {
        return match ($this) {
            self::API => HistoryEntryTrigger::API,
            self::CLI => HistoryEntryTrigger::CLI,
            self::WEB => HistoryEntryTrigger::WEB,
        };
    }
}
