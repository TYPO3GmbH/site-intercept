<?php

declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Enum;

final class SplitterStatus
{
    public const DISPATCH = 'dispatch';
    public const QUEUED = 'queued';
    public const WORK = 'work';
    public const DONE = 'done';
}
