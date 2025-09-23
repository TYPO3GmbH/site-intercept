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
        // If a documentation is rendered with errors, a permalink might temporarily resolve to a 404.
        // We reduce the cache time from a day to a shorter time, so that errors will not a full day
        // @todo - Ideally, the caching could respect the filemtime() of inventory files
        $this->cacheTime = 7200;
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
              '([a-z0-9\-\/_]+):' .       // $repository
              '([a-z0-9\-_]+)' .          // $index
              '(@[a-z0-9\.-]+)?' .        // $version
              '$/imsU',
              $url,
              $matches)
        ) {
            [, $repository, $index] = $matches;
            $repository = mb_strtolower($repository);
            $index = mb_strtolower($index);
            $version = mb_strtolower(str_replace('@', '', $matches[3] ?? '') ?: self::MAIN_IDENTIFIER);

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
        // This is some simplified logic to parse the JSON.
        // The $index is what is the input permalink, something like 'upgrade-run'.
        // We search the whole objects.inv.json array structure for this specific key.
        // Ideally, it is found in "std:label" (highest priority).
        // Some other keys are also parsed as a fallback.
        // If a key is prefixed like "confval-opcache-save-comments", this prefixed key is contained in std:label.
        // It would have a second match in the "std:confval" array, but there without the "confval-" prefix,
        // but the resolve is done through "std:label".
        // The fallbacks will help if (accidentally) a permalink was made using a filename instead of a real anchor key.
        $docNodes = [
            'std:label', // Highest priority, this is what does 99,99% of all resolving!
            'std:doc',
            'std:title',
            'std:option',
            'php:class',
            'php:method',
            'php:interface',
            'php:property',
        ];

        // Sort the JSON array to use the priority above. All unknown keys retain their order.
        uksort($json, static function (string $keyA, string $keyB) use ($docNodes): int {
            $indexA = array_search($keyA, $docNodes, true);
            $indexB = array_search($keyB, $docNodes, true);

            // Keys in the desired order come first and are sorted according to their position in the desired order array $docNodes
            if ($indexA !== false && $indexB !== false) {
                return $indexA <=> $indexB;
            }

            // Keys not in the desired order retain their relative position
            if ($indexA !== false) {
                return -1;
            }

            if ($indexB !== false) {
                return 1;
            }

            return 0;
        });

        foreach ($json as $subKeys) {
            foreach ($subKeys as $indexName => $indexMetaData) {
                // Note: In the future, we may want to do a check for
                // in_array($mainKey, $docNodes, true)
                // ($mainKey would be the key of the foreach($json) loop)
                // to differentiate between a match contained in the $docNodes
                // array above, or a fallback match. For now, this all just leads
                // to the resolved links like 'ApiOverview/Events/Events/Core/Security/InvestigateMutationsEvent.html#typo3-cms-core-security-contentsecuritypolicy-event-investigatemutationsevent-policy'
                if ($indexName === $index) {
                    return $indexMetaData[2];
                }
            }
        }

        return '';
    }

    // Note: Currently hardcoded to 'en-us'
    private function resolveEntryPoint(string $repository, string $version): string
    {
        $useCoreVersionResolving = true;
        if (preg_match('/^typo3[\/-](cms-[0-9a-z\-]+)$/i', $repository, $repositoryParts)) {
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
            $resolvedVersion = Typo3VersionMapping::tryFrom($version)?->getVersion() ?? $version;
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
