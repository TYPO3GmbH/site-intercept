<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller;

use App\Service\DocsAssetsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Provides actions to generate "static" assets used by docs and TER
 */
class AssetsDocsController extends AbstractController
{
    /**
     * @var DocsAssetsService
     */
    private $assetsService;

    public function __construct(DocsAssetsService $assetsService)
    {
        $this->assetsService = $assetsService;
    }

    /**
     * @Route("/assets/docs/manuals.json", name="assets_docs_manuals")
     *
     * @return Response
     */
    public function manuals(): Response
    {
        $aggregatedExtensions = $this->assetsService->aggregateManuals();

        return JsonResponse::create($aggregatedExtensions);
    }

    /**
     * @Route("/assets/docs/extensions.js", name="assets_docs_extensions")
     *
     * @return Response
     */
    public function extensions(): Response
    {
        $javaScript = $this->assetsService->generateExtensionJavaScript();

        return Response::create($javaScript, 200, ['Content-Type' => 'text/javascript']);
    }
}
