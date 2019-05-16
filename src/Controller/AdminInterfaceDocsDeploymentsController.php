<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller;

use App\Enum\DocumentationStatus;
use App\Repository\DocumentationJarRepository;
use App\Service\BambooService;
use App\Service\DocumentationBuildInformationService;
use App\Service\GraylogService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Show and manipulate all docs deployments managed by intercept
 */
class AdminInterfaceDocsDeploymentsController extends AbstractController
{
    /**
     * @Route("/admin/docs/deployments", name="admin_docs_deployments")
     *
     * @param BambooService $bambooService
     * @param GraylogService $graylogService
     * @param DocumentationJarRepository $documentationJarRepository
     * @return Response
     */
    public function index(BambooService $bambooService, GraylogService $graylogService, DocumentationJarRepository $documentationJarRepository): Response
    {
        $recentLogsMessages = $graylogService->getRecentBambooDocsActions();
        $deployments = $documentationJarRepository->findAll();

        return $this->render(
            'docsDeployments.html.twig',
            [
                'logMessages' => $recentLogsMessages,
                'deployments' => $deployments,
                'bambooStatus' => $bambooService->getBambooStatus(),
                'docsLiveServer' => getenv('DOCS_LIVE_SERVER'),
            ]
        );
    }

    /**
     * @Route("/admin/docs/deployments/delete/{documentationJarId}/confirm", name="admin_docs_deployments_delete_view", requirements={"documentationJarId"="\d+"}, methods={"GET"})
     *
     * @param int $documentationJarId
     * @param DocumentationJarRepository $documentationJarRepository
     * @return Response
     */
    public function deleteConfirm(int $documentationJarId, DocumentationJarRepository $documentationJarRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_DOCUMENTATION_MAINTAINER');

        $jar = $documentationJarRepository->find($documentationJarId);
        if (null === $jar || !$jar->isDeletable()) {
            return $this->redirectToRoute('admin_docs_deployments');
        }

        return $this->render(
            'docsDeploymentsDelete.html.twig',
            [
                'deployment' => $jar,
                'docsLiveServer' => getenv('DOCS_LIVE_SERVER'),
            ]
        );
    }

    /**
     * @Route("/admin/docs/deployments/delete/{documentationJarId}", name="admin_docs_deployments_delete_action", requirements={"documentationJarId"="\d+"}, methods={"DELETE"})
     *
     * @param int $documentationJarId
     * @param LoggerInterface $logger
     * @param DocumentationJarRepository $documentationJarRepository
     * @param EntityManagerInterface $entityManager
     * @param DocumentationBuildInformationService $documentationBuildInformationService
     * @param BambooService $bambooService
     * @return Response
     * @throws \App\Exception\DocsPackageDoNotCareBranch
     */
    public function delete(
        int $documentationJarId,
        LoggerInterface $logger,
        DocumentationJarRepository $documentationJarRepository,
        EntityManagerInterface $entityManager,
        DocumentationBuildInformationService $documentationBuildInformationService,
        BambooService $bambooService
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_DOCUMENTATION_MAINTAINER');

        $jar = $documentationJarRepository->find($documentationJarId);

        if (null !== $jar && $jar->isDeletable()) {
            $informationFile = $documentationBuildInformationService->generateBuildInformationFromDocumentationJar($jar);
            $documentationBuildInformationService->dumpDeploymentInformationFile($informationFile);
            $bambooBuildTriggered = $bambooService->triggerDocumentationDeletionPlan($informationFile);

            $jar
                ->setBuildKey($bambooBuildTriggered->buildResultKey)
                ->setStatus(DocumentationStatus::STATUS_DELETING);
            $entityManager->persist($jar);
            $entityManager->flush();

            $logger->info(
                'Documentation deleted.',
                [
                    'type' => 'docsRendering',
                    'status' => 'packageDeleted',
                    'triggeredBy' => 'interface',
                    'repository' => $jar->getRepositoryUrl(),
                    'package' => $jar->getPackageName(),
                    'bambooKey' => $bambooBuildTriggered->buildResultKey,
                ]
            );
        }

        return $this->redirectToRoute('admin_docs_deployments');
    }
}
