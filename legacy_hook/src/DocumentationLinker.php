<?php
declare(strict_types = 1);

namespace App;

/*
 * This file is part of the package t3g/intercept-legacy-hook.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\CacheItem;
use T3Docs\VersionHandling\DefaultInventories;

/**
 * Redirect to a specify interlink target, example:
 *
 * linkToDocs.php?shortcode=t3renderguides:available-default-inventories
 * -> forwards to: https://docs.typo3.org/other/t3docs/render-guides/main/en-us/Developer/InterlinkInventories.html#available-default-inventories
 *
 * linkToDocs.php?shortcode=t3coreapi:caching@12.4
 * -> forwards to: https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/ApiOverview/CachingFramework/Index.html#caching
 *
 * linkToDocs.php?shortcode=t3coreapi:caching@11.5
 * -> forwards to: https://docs.typo3.org/m/typo3/reference-coreapi/11.5/en-us/ApiOverview/CachingFramework/Index.html#caching
 *
 * linkToDocs.php?shortcode=t3coreapi:caching@main
 * -> forwards to: https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/CachingFramework/Index.html#caching
 *
 * Also, all TYPO3 core extensions can be resolved via "typo3-cms-XXX" prefixing:
 * linkToDocs.php?shortcode=typo3-cms-seo:introduction@main
 * -> forwards to: https://docs.typo3.org/c/typo3/cms-seo/main/en-us/Introduction/Index.html#introduction
 *
 * The parts of 'shortcode' URI GET/POST param 'shortcode' are:
 *
 * "$InterlinkRepositoryShortcode : $IndexName @ $typo3Version"
 *
 * If no version is specified, "main" will be used.
 *
 * List of available interlink repositories:
 * @see https://docs.typo3.org/other/t3docs/render-guides/main/en-us/Developer/InterlinkInventories.html#available-default-inventories
 *
 * As of this writing (2024-09-24):
 * t3docs
 * changelog
 * t3coreapi
 * t3tca
 * t3tsconfig
 * t3tsref
 * t3viewhelper
 * t3editors
 * t3install
 * t3upgrade
 * t3sitepackage
 * t3start
 * t3translate
 * t3ts45
 * h2document
 * t3content
 * t3contribute
 * t3writing
 * t3org
 * fluid
 * t3renderguides
 * t3exceptions
 *
 * This redirection works, because all rendered docs are hosted on docs.typo3.org,
 * and inside a special file (inventories.json) the final URLs can be looked up
 * based on a shortcode:
 *
 * Official manuals:
 * https://docs.typo3.org/m/XXX/main/en-us/objects.inv.json
 *
 * Core TYPO3 Extension manuals:
 * https://docs.typo3.org/c/typo3/cms-XXX/main/en-us/objects.inv.json
 *
 * Additional TYPO3 Manuals:
 * https://docs.typo3.org/other/typo3/cms-XXX/main/en-us/objects.inv.json
 *
 * Public TYPO3 extensions (not within the scope of redirection at the moment):
 * https://docs.typo3.org/p/XXX/YYY/main/en-us/objects.inv.json
 *
 * The logic is this:
 *
 * - Explode shortcode into the different parts "$repository", "$index", "$version"
 * - Detect base directory (c, m, p, other) based on "$repository"
 * - Do a file lookup and check for objects.inv.json
 * - Decode JSON, search for $index in the list
 * - Retrieve full URL
 * - Forward to full URL
 *
 * Examples for actual JSON files (one per c/m/p/other):
 * https://docs.typo3.org/c/typo3/cms-seo/main/en-us/objects.inv.json
 * https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/objects.inv.json
 * https://docs.typo3.org/other/t3docs/render-guides/objects.inv.json
 * https://docs.typo3.org/p/georgringer/news/main/en-us/objects.inv.json
 */
final readonly class DocumentationLinker
{
    private FilesystemAdapter $cache;
    private int $cacheTime;

    public function __construct(private ServerRequestInterface $request)
    {
        $this->cacheTime = 86400;
        $this->cache = new FilesystemAdapter('DocumentationLinker', $this->cacheTime);
    }

    /**
     * Output list of versions
     */
    public function redirectToLink(): Response
    {
        $url = $this->request->getQueryParams()['shortcode'] ?? '';

        $cacheItem = $this->cache->getItem('shortcode_' . hash('xxh3', $url));
        if ($cacheItem->isHit()) {
            $cacheData = $cacheItem->get();
            $cacheData['params']['X-Cached-Shortcode'] = 1;
            return new Response($cacheData['code'], $cacheData['params'], $cacheData['message']);
        }

        if (preg_match(
            '/^' .
            '([a-z0-9\-_]+):' .         // $repository
            '([a-z0-9\-_]+)' .          // $index
            '(@[a-z0-9\.-]+)?' .        // $version
            '$/imsU',
            $url,
            $matches)) {
            [, $repository, $index] = $matches;
            $version = str_replace('@', '', $matches[3] ?? '');

            if ($version === '') {
                $version = 'main';
            }

            $entrypoint = $this->resolveEntryPoint($repository, $version);

            $objectsContents = $this->getObjectsFile($entrypoint);

            if ($objectsContents === '') {
                return $this->returnAndCacheResult($cacheItem, 404, [], 'Invalid shortcode, no objects.inv.json found.');
            }

            if (function_exists('json_validate') && !json_validate($objectsContents)) {
                return $this->returnAndCacheResult($cacheItem, 404, [], 'Invalid shortcode, defective objects.inv.json.');
            }

            $json = json_decode($objectsContents, true);
            if (!is_array($json)) {
                return $this->returnAndCacheResult($cacheItem, 404, [], 'Invalid shortcode, invalid objects.inv.json.');
            }

            $link = $this->parseInventoryForIndex($index, $json);

            if ($link === '') {
                return $this->returnAndCacheResult($cacheItem, 404, [], 'Invalid shortcode, could not find index.');
            }

            $forwardUrl = 'https://docs.typo3.org/' . $entrypoint . $link;

            return $this->returnAndCacheResult($cacheItem, 307, ['Location' => $forwardUrl], 'Redirect to ' . $forwardUrl);
        }

        return $this->returnAndCacheResult($cacheItem, 404, [], 'Invalid shortcode.');
    }

    private function returnAndCacheResult(cacheItem $cacheItem, int $code, array $params, string $message): Response
    {
        $cacheItem->set([
            'code' => $code,
            'params' => $params,
            'message' => $message,
        ]);
        $cacheItem->expiresAfter($this->cacheTime);
        $this->cache->save($cacheItem);
        $params['X-Cached-Shortcode'] = 0;
        return new Response($code, $params, $message);
    }

    private function parseInventoryForIndex(string $index, array $json): string
    {
        // This is some VERY simplified logic to parse the JSON.
        // @todo may need refinement. This logic is deeply coupled to the phpdocumentor/guides parser
        // We recognize a matching index in these groups to be the final
        // URL with a fragment identifier:
        $docNodes = [
            'std:doc' => '',
            'std:label' => '',
            'std:title' => '',
            'std:option' => '',
        ];
        // Everything NOT in this array uses a key like 'std:confval'
        // to prefix 'confval'. Known: std:confval, std:confval-menu,

        $link = '';
        foreach ($json as $mainKey => $subKeys) {
            foreach ($subKeys as $indexName => $indexMetaData) {
                if ($indexName === $index) {
                    if (isset($docNodes[$mainKey])) {
                        // Resolves to an entry like 'ApiOverview/Events/Events/Core/Security/InvestigateMutationsEvent.html#typo3-cms-core-security-contentsecuritypolicy-event-investigatemutationsevent-policy'
                        $link = $indexMetaData[2];
                    } else {
                        $docNodeTypeParts = explode(':', $mainKey);
                        // We make the link more specific by replacing something like
                        // std:confval + pagelink.html#some-entry
                        // to:
                        // pagelink.html#confval-some-entry
                        $link = str_replace('#', '#-' . $docNodeTypeParts[1], $indexMetaData[2]);
                    }
                }
            }
        }

        return $link;
    }

    // Note: Currently hardcoded to 'en-us'
    private function resolveEntryPoint(string $repository, string $version): string
    {
        if (preg_match('/^typo3-(cms-[0-9a-z\-]+)$/i', $repository, $repositoryParts)) {
            $entrypoint = 'https://docs.typo3.org/c/typo3/' . strtolower($repositoryParts[1]) . '/{typo3_version}/en-us/';
        } elseif ($inventory = DefaultInventories::tryFrom($repository)) {
            $entrypoint = $inventory->getUrl();
        } else {
            // $entrypoint = 'https://docs.typo3.org/p/' . strtolower($repository) . '/{typo3_version}/en-us/';
            // The '/p/' notation is currently out-of-scope. Would need special handling of slashes.
            $entrypoint = '';
        }

        // Do replacements.
        // The 'https://docs.typo3.org/' notation comes from the external dependency,
        // normalize it again here, strip any hostname component to only get a directory.
        // @todo maybe make this prettier, security-wise this only allows domain names coming from DefaultInventories.
        $entrypoint = str_replace('{typo3_version}', $version, $entrypoint);
        return preg_replace('/^.*:\/\/[^\/]+\//msU', '', $entrypoint);
    }

    private function getObjectsFile(string $entrypoint): string
    {
        $documentRoot = $GLOBALS['_SERVER']['DOCUMENT_ROOT'];
        $filePathToDocsEntryPoint = $documentRoot . '/' . $entrypoint;
        if (!is_dir($filePathToDocsEntryPoint)) {
            return '';
        }

        $objectsFile = $filePathToDocsEntryPoint . 'objects.inv.json';
        if (!is_file($objectsFile)) {
            return '';
        }

        return file_get_contents($objectsFile);
    }

}
