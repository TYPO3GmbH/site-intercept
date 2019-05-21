<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\DocumentationJar;
use App\Repository\DocumentationJarRepository;
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
     * @return Response
     */
    public function index(
        DocumentationJarRepository $documentationJarRepository
    ): Response {
        $extensions = $documentationJarRepository->findCommunityExtensions();
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
                'rendered' => $extension->getLastRenderedAt()->format(\DateTimeInterface::ATOM)
            ];
        }

        return JsonResponse::create($aggregatedExtensions);
    }

    private function buildDocsLink(DocumentationJar $documentationJar): string
    {
        $server = getenv('DOCS_LIVE_SERVER');
        return sprintf('%s%s/%s/%s/en-us', $server, $documentationJar->getTypeShort(), $documentationJar->getPackageName(), $documentationJar->getTargetBranchDirectory());
    }
}
