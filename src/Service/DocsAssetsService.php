<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Cache\DocsAssetsCache;
use App\Entity\DocumentationJar;
use App\Repository\DocumentationJarRepository;

/**
 * Prepare data for use in generated assets
 */
class DocsAssetsService
{
    /**
     * @var DocumentationJarRepository
     */
    private $repository;

    /**
     * @var DocsAssetsCache
     */
    private $cache;

    public function __construct(DocumentationJarRepository $documentationJarRepository, DocsAssetsCache $cache)
    {
        $this->repository = $documentationJarRepository;
        $this->cache = $cache;
    }

    /**
     * @return array
     */
    public function aggregateManuals(): array
    {
        return $this->getFromCacheOrCompose('aggregated_extensions', function () {
            $extensions = $this->repository->findCommunityExtensions();
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
                    'rendered' => $extension->getLastRenderedAt()->format(\DateTimeInterface::ATOM),
                ];

                $aggregatedExtensions[$extension->getExtensionKey()]['docs'][$extension->getTargetBranchDirectory()] = [
                    'url' => $this->buildDocsLink($extension),
                    'rendered' => $extension->getLastRenderedAt()->format(\DateTimeInterface::ATOM),
                ];
            }

            return $aggregatedExtensions;
        });
    }

    /**
     * @return string
     */
    public function generateExtensionJavaScript(): string
    {
        return $this->getFromCacheOrCompose('extensions_js', function () {
            $extensions = $this->repository->findAllExtensions();

            $template = implode("\r\n", [
                '// This file has been automatically generated on %s',
                '// DO NOT MODIFY THIS FILE',
                'var extensionList = %s;',
            ]);
            $flatList = [];

            foreach ($extensions as $extension) {
                if (!isset($flatList[$extension->getExtensionKey()])) {
                    $flatList[$extension->getExtensionKey()] = [
                        'key' => $extension->getExtensionKey(),
                        'latest' => null, // this will be set later
                        'versions' => [$extension->getTargetBranchDirectory()],
                    ];
                } else {
                    $flatList[$extension->getExtensionKey()]['versions'][] = $extension->getTargetBranchDirectory();
                }
            }

            // Sort versions
            foreach ($flatList as &$item) {
                natsort($item['versions']);

                // natsort() keeps the array keys, this is unwanted
                $item['versions'] = array_values(array_reverse($item['versions']));

                // As the items are sorted as expected now, we can safely set the latest stable version
                $stableVersions = array_values(array_filter($item['versions'], static function (string $version): bool {
                    // Remove any item not being a version number
                    return preg_match('/\d+.\d+(.\d+)?/', $version) === 1;
                }));

                $item['latest'] = $stableVersions[0] ?? $item['versions'][0];
            }
            unset($item);

            $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
            $encoded = json_encode(array_values($flatList));

            return sprintf($template, $now->format(\DateTimeInterface::ATOM), $encoded);
        });
    }

    public function invalidate(): void
    {
        $this->cache->delete('aggregated_extensions');
        $this->cache->delete('extensions_js');
    }

    /**
     * Wrapper method that either gets data from cache by its cache identifier or composes the data as defined in its
     * callback function. The composition result is stored in the cache then.
     *
     * @param string $cacheIdentifier
     * @param callable $composeCallback
     * @return mixed
     */
    private function getFromCacheOrCompose(string $cacheIdentifier, callable $composeCallback)
    {
        if (($data = $this->cache->get($cacheIdentifier)) === null) {
            $data = $composeCallback();
            $this->cache->set($cacheIdentifier, $data);
        }

        return $data;
    }

    /**
     * Render a publicly available link to the rendered documentation.
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
