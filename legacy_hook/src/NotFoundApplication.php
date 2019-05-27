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
 * Return a list of other versions of given documentation
 */
class NotFoundApplication
{
    /**
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * @var string
     */
    protected $defaultNotFoundPage = '/404.html';

    public function __construct(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    public function handle(): Response
    {
        $notFoundPage = $this->findNextNotFoundPage();
        if (empty($notFoundPage)) {
            $notFoundPage = $this->getDocumentRoot() . $this->defaultNotFoundPage;
        }
        return new Response(200, [], file_get_contents($notFoundPage));
    }

    protected function findNextNotFoundPage(): string
    {
        // Simple path traversal protection: remove '/../' and '/./'
        $path = str_replace(['/../', '/./'], '', $this->request->getUri()->getPath());

        $pathParts = explode('/', $path);
        $notFoundPath = '';
        while (count($pathParts) > 0) {
            $tmpPath = $this->getDocumentRoot() . '/' . implode('/', $pathParts) . '/404.html';
            if (file_exists($tmpPath)) {
                $notFoundPath = $tmpPath;
                break;
            }
            array_pop($pathParts);
        }
        return $notFoundPath;
    }

    protected function getDocumentRoot(): string
    {
        return $GLOBALS['_SERVER']['DOCUMENT_ROOT'];
    }
}
