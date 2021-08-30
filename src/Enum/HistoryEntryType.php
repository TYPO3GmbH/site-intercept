<?php

declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Enum;

class HistoryEntryType
{
    public const DOCS_RENDERING = 'docsRendering';
    public const DOCS_REDIRECT = 'docsRedirect';
    public const PATCH = 'patch';
    public const TAG = 'tag';
}
