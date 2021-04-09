<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig filter that generates a link for a given documentation record
 */
class InterceptDocsServerLink extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('intercept_docs_server_link', [$this, 'render'], ['is_safe' => ['html']]),
        ];
    }

    public function render(string $value): string
    {
        $server = $_ENV['DOCS_LIVE_SERVER'] ?? '';
        $path = strpos($value, '/') === 0 ? substr($value, 1) : $value;
        return $server . $path;
    }

    public function getName(): string
    {
        return 'intercept_docs_server_link';
    }
}
