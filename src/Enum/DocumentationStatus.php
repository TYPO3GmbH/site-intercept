<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Enum;

final class DocumentationStatus
{
    public const STATUS_RENDERING = 0;
    public const STATUS_RENDERED = 1;
    public const STATUS_DELETING = 2;
    public const STATUS_FAILED = 3;
}
