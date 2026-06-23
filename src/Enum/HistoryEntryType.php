<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Enum;

enum HistoryEntryType: string
{
    case DOCS_RENDERING = 'docsRendering';
    case DOCS_REDIRECT = 'docsRedirect';
    case PATCH = 'patch';
    case TAG = 'tag';
}
