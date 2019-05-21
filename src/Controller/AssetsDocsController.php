<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller;

use App\Repository\DocumentationJarRepository;
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
     * @Route("/assets/docs/manuals.json", name="assets_docs_manuals")
     *
     * @param DocumentationJarRepository $documentationJarRepository
     * @param DocsAssetsService $assetsService
     * @return Response
     */
    public function index(
        DocumentationJarRepository $documentationJarRepository,
        DocsAssetsService $assetsService
    ): Response {
        $extensions = $documentationJarRepository->findCommunityExtensions();
        $aggregatedExtensions = $assetsService->aggregateManuals($extensions);

        return JsonResponse::create($aggregatedExtensions);
    }
}
