<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Utility;

use App\Entity\DocumentationJar;

/**
 * Helper class for documentation related tasks
 */
class DocsUtility
{
    /**
     * Render a publicly available link to the rendered documentation.
     *
     * @param DocumentationJar $documentationJar
     * @return string
     */
    public static function generateLinkToDocs(DocumentationJar $documentationJar): string
    {
        $server = getenv('DOCS_LIVE_SERVER');
        return sprintf('%s%s/%s/%s/en-us', $server, $documentationJar->getTypeShort(), $documentationJar->getPackageName(), $documentationJar->getTargetBranchDirectory());
    }
}
