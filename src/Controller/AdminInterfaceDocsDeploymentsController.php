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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Show and manipulate all docs deployments managed by intercept
 */
class AdminInterfaceDocsDeploymentsController extends AbstractController
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @Route("/admin/docs/deployments", name="admin_docs_deployments")
     *
     * @param Request $request
     * @param LoggerInterface $logger
     * @param BambooService $bambooService
     * @param GraylogService $graylogService
     * @return Response
     */
    public function index(
        Request $request,
        LoggerInterface $logger,
        BambooService $bambooService,
        GraylogService $graylogService,
        DocumentationJarRepository $documentationJarRepository
    ): Response {
        $this->logger = $logger;

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
     * @param Request $request
     * @param int $documentationJarId
     * @param LoggerInterface $logger
     * @param DocumentationJarRepository $documentationJarRepository
     * @return Response
     */
    public function deleteConfirm(
        Request $request,
        int $documentationJarId,
        LoggerInterface $logger,
        DocumentationJarRepository $documentationJarRepository
    ): Response {
        $this->logger = $logger;

        $jar = $documentationJarRepository->find($documentationJarId);
        if (null === $jar || !$jar->isActionable()) {
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
     * @Route("/admin/docs/deployments/delete/{documentationJarId}", name="admin_docs_deployments_delete_action", requirements={"documentationJarId"="\d+"}, methods={"GET"})
     *
     * @param Request $request
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
        Request $request,
        int $documentationJarId,
        LoggerInterface $logger,
        DocumentationJarRepository $documentationJarRepository,
        EntityManagerInterface $entityManager,
        DocumentationBuildInformationService $documentationBuildInformationService,
        BambooService $bambooService
    ): Response {
        $this->logger = $logger;

        $jar = $documentationJarRepository->find($documentationJarId);

        if (null !== $jar && $jar->isActionable()) {
            $jar->setStatus(DocumentationStatus::STATUS_DELETING);
            $entityManager->persist($jar);
            $entityManager->flush();
            $informationFile = $documentationBuildInformationService->generateBuildInformationFromDocumentationJar($jar);
            $documentationBuildInformationService->dumpDeploymentInformationFile($informationFile);

            // ToDo: Trigger Bamboo build with delete command
            //$bambooService->triggerDocumentationDeletionPlan($informationFile);

            $this->logger->info(
                'Documentation deleted.',
                [
                    'type' => 'docsRendering',
                    'status' => 'packageDeleted',
                    'triggeredBy' => 'interface',
                    'repository' => $jar->getRepositoryUrl(),
                    'package' => $jar->getPackageName(),
                ]
            );
        }

        return $this->redirectToRoute('admin_docs_deployments');
    }
}
