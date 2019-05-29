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
        $urlPath = '/' . trim(parse_url($url)['path'] ?? '', '/') . '/';

        // Simple path traversal protection: remove '/../' and '/./'
        $urlPath = str_replace(['/../', '/./'], '', $urlPath);

        // _buildinfo link for homepage repository on document root:
        // If a homepage related url is called (main index, the 404 page, no page at all, or something below Home/
        // Then show the _buildinfo of the hompage repository in web document root _buildinfo
        if (isset($urlPath[0]) && in_array($urlPath[0], ['Home', '404.html', 'Index.html', ''], true)) {
            $content = '<dd class="related-link-buildinfo"><a href="/_buildinfo" target="_blank">BUILDINFO</a></dd>';
            return new Response(200, [], $content);
        }

        // Remove leading and trailing slashes again
        $urlPath = trim($urlPath, '/');
        $urlPath = explode('/', $urlPath);
        if (count($urlPath) < 5) {
            return new Response(200, [], '');
        }

        // first three segments are main root of that repo - eg. '[p, lolli42, enetcache]'
        $entryPoint = array_slice($urlPath, 0, 3);
        // 'current' called version, eg. 'master', or '9.5'
        $currentVersion = array_slice($urlPath, 3, 1)[0];
        // 'current' called language eg. 'en-us'
        $currentLanguage = array_slice($urlPath, 4, 1)[0];

        if (empty($currentVersion) || empty($currentLanguage)) {
            return new Response(200, [], '');
        }

        // verify entry path exists with current version and language exists
        // this additionally sanitizes the input url
        $documentRoot = $GLOBALS['_SERVER']['DOCUMENT_ROOT'];
        $filePathToDocsEntryPoint = $documentRoot . '/' . implode('/', $entryPoint);
        if (!is_dir($filePathToDocsEntryPoint)
            || !is_dir($filePathToDocsEntryPoint . '/' . $currentVersion . '/' . $currentLanguage)
        ) {
            return new Response(200, [], '');
        }

        // if now the _buildinfo directory exists, create entry
        $content = '';
        if (is_dir($filePathToDocsEntryPoint . '/' . $currentVersion . '/' . $currentLanguage . '/_buildinfo')) {
            $content = '<dd class="related-link-buildinfo"><a href="/' . implode('/', $entryPoint) . '/' . $currentVersion . '/' . $currentLanguage . '/_buildinfo" target="_blank">BUILDINFO</a></dd>';
        }
        return new Response(200, [], $content);
    }
}
