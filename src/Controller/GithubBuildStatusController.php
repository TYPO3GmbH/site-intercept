<?php

declare(strict_types = 1);

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
use App\Service\RenderDocumentationService;
use DateTime;
use JsonException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Triggered by github with current build status.
 */
class GithubBuildStatusController extends AbstractController
{
    /**
     * @Route("/github/rendering-done", name="github_rendering_done")
     * @param Request $request
     * @param LoggerInterface $logger
     * @param RenderDocumentationService $renderDocumentationService
     * @return Response
     */
    public function index(
        Request $request,
        LoggerInterface $logger,
        RenderDocumentationService $renderDocumentationService
    ): Response {
        $this->verifyAccess($request);
        $result = new GithubBuildInfo($request);
        $buildKey = $result->buildKey;
        $success = $result->success;
        // This is a back-channel triggered by Github after a "documentation rendering" build is done
        $manager = $this->getDoctrine()->getManager();
        $documentationJarRepository = $this->getDoctrine()->getRepository(DocumentationJar::class);
        /** @var DocumentationJar $documentationJar */
        $documentationJar = $documentationJarRepository->findOneBy(['buildKey' => $buildKey]);
        if ($documentationJar instanceof DocumentationJar) {
            if ($success) {
                // Build was successful, set status to "rendered"
                $documentationJar
                    ->setLastRenderedAt(new DateTime('now'))
                    ->setStatus(DocumentationStatus::STATUS_RENDERED);
                $logger->info(
                    'Documentation rendered on Github',
                    [
                        'type' => 'docsRendering',
                        'status' => 'documentationRendered',
                        'triggeredBy' => 'api',
                        'repository' => $documentationJar->getRepositoryUrl(),
                        'package' => $documentationJar->getPackageName(),
                        'bambooKey' => $buildKey,
                        'link' => $result->link,
                    ]
                );
            } else {
                // Build failed, set status of documentation to "rendering failed"
                $documentationJar->setStatus(DocumentationStatus::STATUS_RENDERING_FAILED);
                $logger->warning(
                    'Failed to render documentation',
                    [
                        'type' => 'docsRendering',
                        'status' => 'documentationRenderingFailed',
                        'triggeredBy' => 'api',
                        'repository' => $documentationJar->getRepositoryUrl(),
                        'package' => $documentationJar->getPackageName(),
                        'bambooKey' => $buildKey,
                        'link' => $result->link,
                    ]
                );
            }
            // If a re-rendering is registered, trigger docs rendering again and unset flag
            if ($documentationJar->isReRenderNeeded()) {
                $renderDocumentationService->renderDocumentationByDocumentationJar($documentationJar, 'api');
                $documentationJar->setReRenderNeeded(false);
            }
            $manager->flush();
        }
        return new Response();
    }

    /**
     * @Route("/github/rendering-started", name="github_rendering_started")
     * @param Request $request
     * @param LoggerInterface $logger
     * @return Response
     */
    public function renderingStart(
        Request $request,
        LoggerInterface $logger
    ): Response {
        $this->verifyAccess($request);
        $result = new GithubBuildInfo($request);
        $buildKey = $result->buildKey;
        // This is a back-channel triggered by Github after a "documentation rendering" build is done
        $manager = $this->getDoctrine()->getManager();
        $documentationJarRepository = $this->getDoctrine()->getRepository(DocumentationJar::class);
        /** @var DocumentationJar $documentationJar */
        $documentationJar = $documentationJarRepository->findOneBy(['buildKey' => $buildKey]);
        if ($documentationJar instanceof DocumentationJar) {
            $logger->info(
                'Documentation rendering on Github started',
                [
                    'type' => 'docsRendering',
                    'status' => 'documentationRendered',
                    'triggeredBy' => 'api',
                    'repository' => $documentationJar->getRepositoryUrl(),
                    'package' => $documentationJar->getPackageName(),
                    'bambooKey' => $buildKey,
                    'link' => $result->link,
                ]
            );
            $manager->flush();
        }
        return new Response();
    }

    /**
     * @Route("/github/deletion-done", name="github_deletion_done")
     * @param Request $request
     * @param LoggerInterface $logger
     * @return Response
     */
    public function deletionDone(
        Request $request,
        LoggerInterface $logger
    ): Response {
        $this->verifyAccess($request);
        $result = new GithubBuildInfo($request);
        $buildKey = $result->buildKey;
        $success = $result->success;
        // This is a back-channel triggered by Github after a "documentation rendering" build is done
        $documentationJarRepository = $this->getDoctrine()->getRepository(DocumentationJar::class);
        /** @var DocumentationJar $documentationJar */
        $documentationJar = $documentationJarRepository->findOneBy(['buildKey' => $buildKey]);
        if ($documentationJar instanceof DocumentationJar) {
            if ($success) {
                // Build was successful, set status to "deleted"
                $documentationJar
                    ->setLastRenderedAt(new DateTime('now'))
                    ->setStatus(DocumentationStatus::STATUS_DELETED);
                $logger->info(
                    'Documentation deleted by Github',
                    [
                        'type' => 'docsRendering',
                        'status' => 'documentationDeleted',
                        'triggeredBy' => 'api',
                        'repository' => $documentationJar->getRepositoryUrl(),
                        'package' => $documentationJar->getPackageName(),
                        'bambooKey' => $buildKey,
                        'link' => $result->link,
                    ]
                );
            }
        }
        return new Response();
    }

    /**
     * @param Request $request
     */
    private function verifyAccess(Request $request): void
    {
        try {
            // prepare content format for hashing
            $content = json_encode($request->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            $signature = 'sha1=' . hash_hmac('sha1', $content, $_ENV['GITHUB_HOOK_SECRET'] ?? '');
            // verify hook payload
        } catch (JsonException $e) {
            throw new AccessDeniedHttpException('Invalid payload');
        }
        if (!hash_equals($signature, $request->headers->get('x-hub-signature'))) {
            throw new AccessDeniedHttpException('Non-matching signatures');
        }
    }
}
