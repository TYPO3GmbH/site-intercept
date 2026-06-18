<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Enum;

enum RepositoryDomainStatus: int
{
    case UNKNOWN = -1;
    case ALLOWED = 1;
    case DISALLOWED = 0;

    public function getLabel(): string
    {
        return match ($this) {
            self::UNKNOWN => 'Unknown',
            self::ALLOWED => 'Allowed',
            self::DISALLOWED => 'Disallowed',
        };
    }
}
