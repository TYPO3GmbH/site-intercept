<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller\AdminInterface\Docs;

use App\Enum\DocumentationStatus;
use App\Exception\ComposerJsonInvalidException;
use App\Exception\DocsPackageDoNotCareBranch;
use App\Exception\DuplicateDocumentationRepositoryException;
use App\Exception\UnsupportedWebHookRequestException;
use App\Form\DocsDeploymentFilterType;
use App\Repository\DocumentationJarRepository;
use App\Service\BambooService;
use App\Service\DocumentationBuildInformationService;
use App\Service\GithubService;
use App\Service\GraylogService;
use App\Service\RenderDocumentationService;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Show and manipulate all docs deployments managed by intercept
 */
class DeploymentsController extends AbstractController
{
    /**
     * @Route("/admin/docs/deployments", name="admin_docs_deployments")
     *
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @param GraylogService $graylogService
     * @param DocumentationJarRepository $documentationJarRepository
     * @return Response
     */
    public function index(
        Request $request,
        PaginatorInterface $paginator,
        GraylogService $graylogService,
        DocumentationJarRepository $documentationJarRepository
    ): Response {
        $recentLogsMessages = $graylogService->getRecentBambooDocsActions();
        $criteria = Criteria::create();

        $requestSortDirection = $request->query->get('direction');
        $requestSortField = $request->query->get('sort');
        $sortDirection = $requestSortDirection === 'asc' ? Criteria::ASC : Criteria::DESC;
        $sortField = in_array($requestSortField, ['packageName', 'typeLong', 'lastRenderedAt']) ? $requestSortField : 'lastRenderedAt';
        $criteria->orderBy([$sortField => $sortDirection]);

        $form = $this->createForm(DocsDeploymentFilterType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $expressionBuilder = Criteria::expr();
            $data = $form->getData();
            if ($data['type']) {
                $criteria->andWhere($expressionBuilder->eq('typeLong', $data['type']));
            }
            if ($data['status'] || $data['status'] === 0) {
                $criteria->andWhere($expressionBuilder->eq('status', $data['status']));
            }
            if ($data['search']) {
                $criteria->andWhere($expressionBuilder->contains('packageName', $data['search']));
            }
        }

        $deployments = $documentationJarRepository->matching($criteria);
        $pagination = $paginator->paginate(
            $deployments,
            $request->query->getInt('page', 1)
        );

        return $this->render(
            'docs_deployments/index.html.twig',
            [
                'filter' => $form->createView(),
                'pagination' => $pagination,
                'logMessages' => $recentLogsMessages,
                'docsLiveServer' => $_ENV['DOCS_LIVE_SERVER'] ?? '',
            ]
        );
    }

    /**
     * @Route("/admin/docs/deployments/delete/{documentationJarId}/confirm", name="admin_docs_deployments_delete_view", requirements={"documentationJarId"="\d+"}, methods={"GET"})
     * @IsGranted("ROLE_DOCUMENTATION_MAINTAINER")
     * @param int $documentationJarId
     * @param DocumentationJarRepository $documentationJarRepository
     * @return Response
     */
    public function deleteConfirm(
        int $documentationJarId,
        DocumentationJarRepository $documentationJarRepository
    ): Response {
        $jar = $documentationJarRepository->find($documentationJarId);
        if (null === $jar || !$jar->isDeletable()) {
            return $this->redirectToRoute('admin_docs_deployments');
        }

        return $this->render(
            'docs_deployments/delete.html.twig',
            [
                'deployment' => $jar,
                'docsLiveServer' => $_ENV['DOCS_LIVE_SERVER'] ?? '',
            ]
        );
    }

    /**
     * @Route("/admin/docs/deployments/delete/{documentationJarId}", name="admin_docs_deployments_delete_action", requirements={"documentationJarId"="\d+"}, methods={"DELETE"})
     * @IsGranted("ROLE_DOCUMENTATION_MAINTAINER")
     * @param int $documentationJarId
     * @param LoggerInterface $logger
     * @param DocumentationJarRepository $documentationJarRepository
     * @param EntityManagerInterface $entityManager
     * @param DocumentationBuildInformationService $documentationBuildInformationService
     * @param GithubService $githubService
     * @throws DocsPackageDoNotCareBranch
     * @throws ComposerJsonInvalidException
     * @return Response
     */
    public function delete(
        int $documentationJarId,
        LoggerInterface $logger,
        DocumentationJarRepository $documentationJarRepository,
        EntityManagerInterface $entityManager,
        DocumentationBuildInformationService $documentationBuildInformationService,
        GithubService $githubService
    ): Response {
        $jar = $documentationJarRepository->find($documentationJarId);

        if (null !== $jar && $jar->isDeletable()) {
            $informationFile = $documentationBuildInformationService->generateBuildInformationFromDocumentationJar($jar);
            $documentationBuildInformationService->dumpDeploymentInformationFile($informationFile);
            $buildTriggered = $githubService->triggerDocumentationDeletionPlan($informationFile);

            $jar
                ->setBuildKey($buildTriggered->buildResultKey)
                ->setStatus(DocumentationStatus::STATUS_DELETING);
            $entityManager->persist($jar);
            $entityManager->flush();

            $logger->info(
                'Documentation deletion triggered',
                [
                    'type' => 'docsRendering',
                    'status' => 'packageDeleted',
                    'triggeredBy' => 'interface',
                    'repository' => $jar->getRepositoryUrl(),
                    'package' => $jar->getPackageName(),
                    'bambooKey' => $buildTriggered->buildResultKey,
                ]
            );
        }

        return $this->redirectToRoute('admin_docs_deployments');
    }

    /**
     * @Route("/admin/docs/deployments/approve/{documentationJarId}", name="admin_docs_deployments_approve_action", requirements={"documentationJarId"="\d+"})
     * @IsGranted("ROLE_DOCUMENTATION_MAINTAINER")
     * @param int $documentationJarId
     * @param DocumentationJarRepository $documentationJarRepository
     * @param EntityManagerInterface $entityManager
     * @param RenderDocumentationService $renderDocumentationService
     * @return Response
     * @throws DocsPackageDoNotCareBranch
     * @throws DuplicateDocumentationRepositoryException
     */
    public function approve(
        int $documentationJarId,
        DocumentationJarRepository $documentationJarRepository,
        EntityManagerInterface $entityManager,
        RenderDocumentationService $renderDocumentationService
    ): Response {
        $originalJar = $documentationJarRepository->find($documentationJarId);
        $jars = $documentationJarRepository->findBy(['repositoryUrl' => $originalJar->getRepositoryUrl()]);

        foreach ($jars as $jar) {
            $jar->setApproved(true);
            $entityManager->persist($jar);
            $entityManager->flush();
            $renderDocumentationService->renderDocumentationByDocumentationJar($jar, 'interface');
        }

        $this->addFlash('success', 'Repository has been approved.');

        return $this->redirectToRoute('admin_docs_deployments');
    }

    /**
     * @Route("/admin/docs/render", name="admin_docs_render")
     * @IsGranted("ROLE_DOCUMENTATION_MAINTAINER")
     * @param Request $request
     * @param LoggerInterface $logger
     * @param DocumentationJarRepository $documentationJarRepository
     * @param RenderDocumentationService $renderDocumentationService
     * @return Response
     * @throws DuplicateDocumentationRepositoryException
     */
    public function renderDocs(
        Request $request,
        LoggerInterface $logger,
        DocumentationJarRepository $documentationJarRepository,
        RenderDocumentationService $renderDocumentationService
    ): Response {
        try {
            $documentationJarId = (int)$request->get('documentation');
            $documentationJar = $documentationJarRepository->find($documentationJarId);
            if ($documentationJar === null) {
                throw new InvalidArgumentException('no documentationJar given', 1557930900);
            }

            $renderDocumentationService->renderDocumentationByDocumentationJar($documentationJar, 'interface');
            $this->addFlash('success', 'A re-rendering was triggered.');
            return $this->redirectToRoute('admin_docs_deployments');
        } catch (UnsupportedWebHookRequestException $e) {
            // Hook payload could not be identified as hook that should trigger rendering
            $logger->warning(
                'Cannot render documentation: ' . $e->getMessage(),
                [
                    'type' => 'docsRendering',
                    'status' => 'unsupportedHook',
                    'headers' => $request->headers,
                    'payload' => $request->getContent(),
                    'triggeredBy' => 'api',
                    'exceptionCode' => $e->getCode(),
                ]
            );
            return new Response('Invalid hook payload. See https://intercept.typo3.com for more information.', Response::HTTP_PRECONDITION_FAILED);
        } catch (DocsPackageDoNotCareBranch $e) {
            $logger->warning(
                'Can not render documentation: ' . $e->getMessage(),
                [
                    'type' => 'docsRendering',
                    'status' => 'noRelevantBranchOrTag',
                    'triggeredBy' => 'api',
                    'exceptionCode' => $e->getCode(),
                    'exceptionMessage' => $e->getMessage(),
                    'repository' => $documentationJar->getRepositoryUrl(),
                    'sourceBranch' => $documentationJar->getBranch(),
                ]
            );
            return new Response('Branch or tag name ignored for documentation rendering. See https://intercept.typo3.com for more information.', Response::HTTP_PRECONDITION_FAILED);
        }
    }

    /**
     * @Route("/admin/docs/deployments/reset/{documentationJarId}", name="admin_docs_deployments_reset_action", requirements={"documentationJarId"="\d+"}, methods={"GET"})
     * @IsGranted("ROLE_ADMIN")
     * @param int $documentationJarId
     * @param DocumentationJarRepository $documentationJarRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function resetStatus(
        int $documentationJarId,
        DocumentationJarRepository $documentationJarRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $jar = $documentationJarRepository->find($documentationJarId);

        if (null !== $jar) {
            $jar->setStatus(DocumentationStatus::STATUS_RENDERED);
            $entityManager->persist($jar);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_docs_deployments');
    }
}
