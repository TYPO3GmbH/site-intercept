<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Twig;

use App\Entity\DocumentationJar;
use App\Utility\DocsUtility;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig filter that generates a link for a given documentation record.
 */
class InterceptLinkToDocsExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('intercept_link_to_docs', $this->render(...), ['is_safe' => ['html']]),
        ];
    }

    public function render(DocumentationJar $documentationJar): string
    {
        return DocsUtility::generateLinkToDocs($documentationJar);
    }

    public function getName(): string
    {
        return 'intercept_link_to_docs';
    }
}
