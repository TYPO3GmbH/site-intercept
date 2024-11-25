<?php
declare(strict_types = 1);

namespace App;

/*
 * This file is part of the package t3g/intercept-legacy-hook.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use App\Linker\ResponseDescriber;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;
use T3Docs\VersionHandling\DefaultInventories;
use T3Docs\VersionHandling\Typo3VersionMapping;

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
 * linkToDocs.php?shortcode=georgringer-news:start@main
 * -> forwards to: https://docs.typo3.org/p/georgringer/news/en-us/Index.html#start
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
 * https://docs.typo3.org/other/typo3/XXX/main/en-us/objects.inv.json
 *
 * Public TYPO3 extensions (xxx/yyy is the composer packagist key):
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
 *
 * @see Unit Test in legacy_hook/tests/Unit/PermalinksTest.php for examples.
 */
final readonly class DocumentationLinker
{
    private const MAIN_IDENTIFIER = 'main';

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

        $cacheKey = 'shortcode_' . hash('xxh3', $url);
        /** @var ResponseDescriber $responseDescriber */
        $responseDescriber = $this->cache->get($cacheKey, function (ItemInterface $item) use ($url): ResponseDescriber {
            $item->expiresAfter($this->cacheTime);

            return $this->resolvePermalink($url);
        });

        return new Response($responseDescriber->statusCode, $responseDescriber->headers, $responseDescriber->body);
    }

    public function resolvePermalink(string $url): ResponseDescriber
    {
        if (preg_match(
              '/^' .
              '([a-z0-9\-_]+):' .         // $repository
              '([a-z0-9\-_]+)' .          // $index
              '(@[a-z0-9\.-]+)?' .        // $version
              '$/imsU',
              $url,
              $matches)
        ) {
            [, $repository, $index] = $matches;
            $version = str_replace('@', '', $matches[3] ?? '') ?: self::MAIN_IDENTIFIER;
            $entrypoint = $this->resolveEntryPoint($repository, $version);
            $objectsContents = $this->getObjectsFile($entrypoint);

            if ($objectsContents === '' && $version !== self::MAIN_IDENTIFIER) {
                // soft-fail to resolve a maybe not-yet released version to main.
                $entrypoint = $this->resolveEntryPoint($repository, self::MAIN_IDENTIFIER);
                $objectsContents = $this->getObjectsFile($entrypoint);
            }

            if ($objectsContents === '') {
                return new ResponseDescriber(404, [], 'Invalid shortcode, no objects.inv.json found.');
            }

            if (function_exists('json_validate') && !json_validate($objectsContents)) {
                return new ResponseDescriber(404, [], 'Invalid shortcode, defective objects.inv.json.');
            }

            $json = json_decode($objectsContents, true);
            if (!is_array($json)) {
                return new ResponseDescriber(404, [], 'Invalid shortcode, invalid objects.inv.json.');
            }

            $link = $this->parseInventoryForIndex($index, $json);
            if ($link === '') {
                return new ResponseDescriber(404, [], 'Invalid shortcode, could not find index.');
            }

            $forwardUrl = 'https://docs.typo3.org/' . $entrypoint . $link;

            return new ResponseDescriber(307, ['Location' => $forwardUrl], 'Redirect to ' . $forwardUrl);
        }

        return new ResponseDescriber(404, [], 'Invalid shortcode.');
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
                        $link = str_replace('#', '#' . $docNodeTypeParts[1] . '-', $indexMetaData[2]);
                    }
                }
            }
        }

        return $link;
    }

    // Note: Currently hardcoded to 'en-us'
    private function resolveEntryPoint(string $repository, string $version): string
    {
        $useCoreVersionResolving = true;
        if (preg_match('/^typo3-(cms-[0-9a-z\-]+)$/i', $repository, $repositoryParts)) {
            // CASE: TYPO3 core manuals
            $entrypoint = 'https://docs.typo3.org/c/typo3/' . strtolower($repositoryParts[1]) . '/{typo3_version}/en-us/';
        } elseif ($inventory = DefaultInventories::tryFrom($repository)) {
            // CASE: Official TYPO3 Documentation with known inventories. Provides "{typo3_version}" internally
            // (some inventories DO NOT have that and always go to 'main'!)
            $entrypoint = $inventory->getUrl($version);
        } else {
            // CASE: Third party documentation, based on composer-keys like https://docs.typo3.org/p/georgringer/news
            //       A permalink like https://docs.typo3.org/permalink/someVendor-some-extension/ is resolved to https://docs.typo3.org/p/somevendor/some-extension/
            $entrypoint = 'https://docs.typo3.org/p/' . preg_replace('/-/', '/', strtolower($repository), 1) . '/{typo3_version}/en-us/';
            $useCoreVersionResolving = false;
        }

        if ($useCoreVersionResolving) {
            // Core Version resolving. Uses the composer package t3docs/typo3-version-handling which allows to
            // interpret strings as "dev", "stable", "oldstable" and can map "12" to latest 12.4.x version.
            // If not resolvable, uses the raw version number as lookup (for example "12.4"). An invalid version
            // string like "99.9999" will later fail when searching for the directory.
            $resolvedVersionEnum = Typo3VersionMapping::tryFrom($version);
            if ($resolvedVersionEnum === null) {
                $resolvedVersion = $version;
            } else {
                $resolvedVersion = $resolvedVersionEnum->getVersion();
            }
        } else {
            // Third party extensions use their own versioning.
            $resolvedVersion = $version;
        }

        // Do replacements.
        // The 'https://docs.typo3.org/' notation comes from the external dependency,
        // normalize it again here, strip any hostname component to only get a directory.
        // @todo maybe make this prettier, security-wise this only allows domain names coming from DefaultInventories.
        $entrypoint = str_replace('{typo3_version}', $resolvedVersion, $entrypoint);
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
