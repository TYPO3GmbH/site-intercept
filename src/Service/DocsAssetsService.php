<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Cache\DocsAssetsCache;
use App\Entity\DocumentationJar;

/**
 * Prepare data for use in generated assets
 */
class DocsAssetsService
{
    /**
     * @var DocsAssetsCache
     */
    private $cache;

    public function __construct(DocsAssetsCache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param DocumentationJar[] $extensions
     * @return array
     */
    public function aggregateManuals(array $extensions): array
    {
        return $this->getFromCacheOrCompose('aggregated_extensions', function() use ($extensions) {
            $aggregatedExtensions = [];

            foreach ($extensions as $extension) {
                if (!isset($aggregatedExtensions[$extension->getExtensionKey()])) {
                    $aggregatedExtensions[$extension->getExtensionKey()] = [
                        'packageName' => $extension->getPackageName(),
                        'docs' => [],
                    ];
                }

                $aggregatedExtensions[$extension->getExtensionKey()]['docs'][$extension->getBranch()] = [
                    'url' => $this->buildDocsLink($extension),
                    'rendered' => $extension->getLastRenderedAt()->format(\DateTimeInterface::ATOM)
                ];
            }

            return $aggregatedExtensions;
        });
    }

    public function invalidate(): void
    {
        $this->cache->delete('aggregated_extensions');
    }

    /**
     * Wrapper method that either gets data from cache by its cache identifier or composes the data as defined in its
     * callback function. The composition result is stored in the cache then.
     *
     * @param string $cacheIdentifier
     * @param callable $composeCallback
     * @return array
     */
    private function getFromCacheOrCompose(string $cacheIdentifier, callable $composeCallback): array
    {
        if (($data = $this->cache->get($cacheIdentifier)) === null) {
            $data = $composeCallback();
            $this->cache->set($cacheIdentifier, $data);
        }

        return $data;
    }

    /**
     * Render a publicly available link to the rendered documentation.
     * TODO: Make method public (and available in twig?)
     *
     * @param DocumentationJar $documentationJar
     * @return string
     */
    private function buildDocsLink(DocumentationJar $documentationJar): string
    {
        $server = getenv('DOCS_LIVE_SERVER');
        return sprintf('%s%s/%s/%s/en-us', $server, $documentationJar->getTypeShort(), $documentationJar->getPackageName(), $documentationJar->getTargetBranchDirectory());
    }
}
