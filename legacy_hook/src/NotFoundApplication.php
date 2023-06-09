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
 * Handles 404 state for docs.typo3.org
 */
readonly class NotFoundApplication
{
    private const DEFAULT_NO_FOUND_PAGE = '/404.html';

    public function __construct(private ServerRequestInterface $request)
    {
    }

    public function handle(): Response
    {
        $notFoundPage = $this->findNextNotFoundPage();
        if (empty($notFoundPage)) {
            $notFoundPage = $this->getDocumentRoot() . self::DEFAULT_NO_FOUND_PAGE;
        }
        return new Response(404, [], $this->getNotFoundContent($notFoundPage));
    }

    protected function findNextNotFoundPage(): string
    {
        // Simple path traversal protection: remove '/../' and '/./'
        $path = str_replace(['/../', '/./'], '', $this->request->getUri()->getPath());

        $pathParts = explode('/', trim($path, '/'));
        $notFoundPath = '';
        while (count($pathParts) > 0) {
            $tmpPath = $this->getDocumentRoot() . '/' . implode('/', $pathParts) . '/404.html';
            if (@file_exists($tmpPath)) {
                $notFoundPath = $tmpPath;
                break;
            }
            array_pop($pathParts);
        }
        return $notFoundPath;
    }

    protected function getNotFoundContent(string $notFoundFile): string
    {
        $content = file_get_contents($notFoundFile);

        $tagBeforeBaseTag = '<meta charset="utf-8"/>';
        $baseTag = '<base href="https://docs.typo3.org/">';

        return str_replace(
            $tagBeforeBaseTag,
            $tagBeforeBaseTag. PHP_EOL . $baseTag,
            $content
        );
    }

    protected function getDocumentRoot(): string
    {
        return rtrim($GLOBALS['_SERVER']['DOCUMENT_ROOT'] ?? '', '/');
    }
}
