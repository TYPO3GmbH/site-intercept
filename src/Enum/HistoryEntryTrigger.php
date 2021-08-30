<?php

declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Enum;

class HistoryEntryTrigger
{
    public const API = 'api';
    public const CLI = 'cli';
    public const WEB = 'interface';
}
