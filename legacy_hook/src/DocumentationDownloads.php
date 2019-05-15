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

/**
 * Return a list of download links of given documentation
 */
class DocumentationDownloads
{
    protected $request;

    public function __construct(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Output list of versions
     */
    public function getDownloads(): Response
    {
        $url = $this->request->getQueryParams()['url'] ?? '';

        // /p/vendor/package/version/some/sub/page/Index.html/
        $urlPath = '/' . trim((parse_url($url)['path']) ?? '', '/') . '/';

        // Simple path traversal protection: remove '/../' and '/./'
        $urlPath = str_replace('/../', '', $urlPath);
        $urlPath = str_replace('/./', '', $urlPath);

        // Remove leading and trailing slashes again
        $urlPath = trim($urlPath, '/');
        $urlPath = explode('/', $urlPath);
        if (count($urlPath) < 4) {
            return new Response(200, [], '');
        }

        // first three segments are main root of that repo - eg. '[p, lolli42, enetcache]'
        $entryPoint = array_slice($urlPath, 0, 3);
        // 'current' called version, eg. 'master', or '9.5'
        $currentVersion = array_slice($urlPath, 3, 1)[0];

        if (empty($currentVersion)) {
            return new Response(200, [], '');
        }

        // verify entry path exists and current version exist
        // this additionally sanitizes the input url
        $documentRoot = $GLOBALS['_SERVER']['DOCUMENT_ROOT'];
        $filePathToDocsEntryPoint = $documentRoot . '/' . implode('/', $entryPoint);
        if (!is_dir($filePathToDocsEntryPoint)
            || !is_dir($filePathToDocsEntryPoint . '/' . $currentVersion)
        ) {
            return new Response(200, [], '');
        }

        // if now the _buildinfo directory exists, create entry
        $content = '';
        if (is_dir($filePathToDocsEntryPoint . '/' . $currentVersion . '/_buildinfo')) {
            $content = '<dd class="related-link-buildinfo"><a href="/ ' . implode('/', $entryPoint) . '/' . $currentVersion . '/_buildinfo" target="_blank">BUILDINFO</a></dd>';
        }
        return new Response(200, [], $content);
    }
}