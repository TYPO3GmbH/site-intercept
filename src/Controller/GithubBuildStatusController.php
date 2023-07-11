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
use App\Enum\DocumentationStatus;
use App\Extractor\GithubBuildInfo;
use App\Repository\DocumentationJarRepository;
use App\Service\RenderDocumentationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Triggered by GitHub with current build status.
 */
class GithubBuildStatusController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DocumentationJarRepository $documentationJarRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route(path: '/github/rendering-done', name: 'github_rendering_done')]
    public function index(
        Request $request,
        RenderDocumentationService $renderDocumentationService
    ): Response {
        $this->assertValidSignature($request);

        $result = new GithubBuildInfo($request);
        // This is a back-channel triggered by GitHub after a "documentation rendering" build is done
        /** @var DocumentationJar $documentationJar */
        $documentationJar = $this->documentationJarRepository->findOneBy(['buildKey' => $result->buildKey]);
        if ($documentationJar instanceof DocumentationJar) {
            if ($result->success) {
                // Build was successful, set status to "rendered"
                $documentationJar
                    ->setLastRenderedAt(new \DateTime('now'))
                    ->setStatus(DocumentationStatus::STATUS_RENDERED)
                    ->setLastRenderedLink($result->link);
                // persist immediately
                $this->entityManager->flush();
                $this->logger->info(
                    'Documentation rendered on Github',
                    [
                        'type' => 'docsRendering',
                        'status' => 'documentationRendered',
                        'triggeredBy' => 'api',
                        'repository' => $documentationJar->getRepositoryUrl(),
                        'package' => $documentationJar->getPackageName(),
                        'bambooKey' => $result->buildKey,
                        'link' => $result->link,
                    ]
                );
            } else {
                // Build failed, set status of documentation to "rendering failed"
                $documentationJar->setStatus(DocumentationStatus::STATUS_RENDERING_FAILED)
                    ->setLastRenderedLink($result->link);
                // persist immediately
                $this->entityManager->flush();
                $this->logger->warning(
                    'Failed to render documentation',
                    [
                        'type' => 'docsRendering',
                        'status' => 'documentationRenderingFailed',
                        'triggeredBy' => 'api',
                        'repository' => $documentationJar->getRepositoryUrl(),
                        'package' => $documentationJar->getPackageName(),
                        'bambooKey' => $result->buildKey,
                        'link' => $result->link,
                    ]
                );
            }
            // If a re-rendering is registered, trigger docs rendering again and unset flag
            if ($documentationJar->isReRenderNeeded()) {
                $renderDocumentationService->renderDocumentationByDocumentationJar($documentationJar, 'api');
                $documentationJar->setReRenderNeeded(false);
                // persist immediately
                $this->entityManager->flush();
            }
        }

        return new Response();
    }

    #[Route(path: '/github/rendering-started', name: 'github_rendering_started')]
    public function renderingStart(Request $request): Response
    {
        $this->assertValidSignature($request);

        $result = new GithubBuildInfo($request);
        // This is a back-channel triggered by GitHub after a "documentation rendering" build is done
        /** @var DocumentationJar $documentationJar */
        $documentationJar = $this->documentationJarRepository->findOneBy(['buildKey' => $result->buildKey]);
        if ($documentationJar instanceof DocumentationJar) {
            $documentationJar->setLastRenderedLink($result->link);
            $this->logger->info(
                'Documentation rendering on Github started',
                [
                    'type' => 'docsRendering',
                    'status' => 'documentationRendered',
                    'triggeredBy' => 'api',
                    'repository' => $documentationJar->getRepositoryUrl(),
                    'package' => $documentationJar->getPackageName(),
                    'bambooKey' => $result->buildKey,
                    'link' => $result->link,
                ]
            );
            $this->entityManager->flush();
        }

        return new Response();
    }

    #[Route(path: '/github/deletion-done', name: 'github_deletion_done')]
    public function deletionDone(Request $request): Response
    {
        $this->assertValidSignature($request);

        $result = new GithubBuildInfo($request);
        // This is a back-channel triggered by GitHub after a "documentation rendering" build is done
        /** @var DocumentationJar $documentationJar */
        $documentationJar = $this->documentationJarRepository->findOneBy(['buildKey' => $result->buildKey]);
        if ($documentationJar instanceof DocumentationJar && $result->success) {
            // Build was successful, set status to "deleted"
            $this->entityManager->remove($documentationJar);
            $this->entityManager->flush();
            $this->logger->info(
                'Documentation deleted by Github',
                [
                    'type' => 'docsRendering',
                    'status' => 'documentationDeleted',
                    'triggeredBy' => 'api',
                    'repository' => $documentationJar->getRepositoryUrl(),
                    'package' => $documentationJar->getPackageName(),
                    'bambooKey' => $result->buildKey,
                    'link' => $result->link,
                ]
            );
        }

        return new Response();
    }

    private function assertValidSignature(Request $request): void
    {
        $expectedSignature = $request->headers->get('x-hub-signature-256') ?? '';
        if ('' === $expectedSignature) {
            throw new AccessDeniedHttpException('Missing payload signature header');
        }

        $signature = 'sha256=' . hash_hmac('sha256', (string) $request->getContent(), $_ENV['GITHUB_HOOK_SECRET'] ?? '');
        if (!hash_equals($expectedSignature, $signature)) {
            throw new AccessDeniedHttpException('Content doesn\'t match expected signature "' . $expectedSignature . '"');
        }
    }
}
