<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Entity\DocumentationJar;

/**
 * Helper class for documentation related tasks.
 */
readonly class DocsService
{
    private string $docsServer;

    public function __construct(
        string $docsServer,
    ) {
        $this->docsServer = rtrim($docsServer, '/');
    }

    public function getDocsServer(): string
    {
        return $this->docsServer;
    }

    /**
     * Render a publicly available link to the rendered documentation.
     */
    public function generateLinkToDocs(DocumentationJar $documentationJar): string
    {
        return sprintf('%s/%s/%s/%s/en-us', rtrim($this->docsServer, '/'), $documentationJar->getTypeShort(), $documentationJar->getPackageName(), $documentationJar->getTargetBranchDirectory());
    }
}
