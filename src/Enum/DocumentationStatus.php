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
    public const STATUS_RENDERING_FAILED = 3;
    public const STATUS_AWAITING_APPROVAL = 4;
    public const STATUS_DELETED = 5;

    public const STATUSSES = [
        self::STATUS_RENDERING => 'Rendering',
        self::STATUS_RENDERED => 'Rendered',
        self::STATUS_DELETING => 'Deleting',
        self::STATUS_RENDERING_FAILED => 'Rendering Failed',
        self::STATUS_AWAITING_APPROVAL => 'Awaiting Approval',
        self::STATUS_DELETED => 'Deleted',
    ];
}
