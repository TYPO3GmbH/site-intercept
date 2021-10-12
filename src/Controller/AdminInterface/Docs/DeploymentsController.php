<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller\AdminInterface\Docs;

use App\Entity\HistoryEntry;
use App\Enum\DocsRenderingHistoryStatus;
use App\Enum\DocumentationStatus;
use App\Enum\HistoryEntryTrigger;
use App\Enum\HistoryEntryType;
use App\Exception\ComposerJsonInvalidException;
use App\Exception\DocsPackageDoNotCareBranch;
use App\Exception\DuplicateDocumentationRepositoryException;
use App\Exception\UnsupportedWebHookRequestException;
use App\Form\DocsDeploymentFilterType;
use App\Repository\DocumentationJarRepository;
use App\Repository\HistoryEntryRepository;
use App\Service\DocumentationBuildInformationService;
use App\Service\GithubService;
use App\Service\RenderDocumentationService;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use T3G\Bundle\Keycloak\Security\KeyCloakUser;

/**
 * Show and manipulate all docs deployments managed by intercept
 */
class DeploymentsController extends AbstractController
{
    /**
     * @Route("/admin/docs/deployments", name="admin_docs_deployments")
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @param HistoryEntryRepository $historyEntryRepository
     * @param DocumentationJarRepository $documentationJarRepository
     * @return Response
     */
    public function index(
        Request $request,
        PaginatorInterface $paginator,
        HistoryEntryRepository $historyEntryRepository,
        DocumentationJarRepository $documentationJarRepository
    ): Response {
        $recentLogsMessages = $historyEntryRepository->findByType('docsRendering', 30);
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
                'warnings' => DocsRenderingHistoryStatus::$warnings,
                'translations' => DocsRenderingHistoryStatus::$messages,
                'docsLiveServer' => $_ENV['DOCS_LIVE_SERVER'] ?? ''
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
            $user = $this->getUser();
            $userIdentifier = 'Anon.';
            if ($user instanceof KeyCloakUser) {
                $userIdentifier = $user->getDisplayName();
            }
            $entityManager->persist(
                (new HistoryEntry())
                    ->setType('docsRendering')
                    ->setStatus(DocsRenderingHistoryStatus::PACKAGE_DELETED)
                    ->setGroupEntry($buildTriggered->buildResultKey)
                    ->setData(
                        [
                            'type' => HistoryEntryType::DOCS_RENDERING,
                            'status' => DocsRenderingHistoryStatus::PACKAGE_DELETED,
                            'triggeredBy' => HistoryEntryTrigger::WEB,
                            'repository' => $jar->getRepositoryUrl(),
                            'package' => $jar->getPackageName(),
                            'bambooKey' => $buildTriggered->buildResultKey,
                            'user' => $userIdentifier
                        ]
                    )
            );
            $entityManager->flush();
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
     * @param EntityManagerInterface $entityManager
     * @param DocumentationJarRepository $documentationJarRepository
     * @param RenderDocumentationService $renderDocumentationService
     * @throws DuplicateDocumentationRepositoryException
     * @return Response
     */
    public function renderDocs(
        Request $request,
        EntityManagerInterface $entityManager,
        DocumentationJarRepository $documentationJarRepository,
        RenderDocumentationService $renderDocumentationService
    ): Response {
        $user = $this->getUser();
        $userIdentifier = 'Anon.';
        if ($user instanceof KeyCloakUser) {
            $userIdentifier = $user->getDisplayName();
        }
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
            $entityManager->persist(
                (new HistoryEntry())
                    ->setType('docsRendering')
                    ->setStatus(DocsRenderingHistoryStatus::UNSUPPORTED_HOOK)
                    ->setData(
                        [
                            'type' => 'docsRendering',
                            'status' => DocsRenderingHistoryStatus::UNSUPPORTED_HOOK,
                            'headers' => $request->headers,
                            'payload' => $request->getContent(),
                            'triggeredBy' => HistoryEntryTrigger::API,
                            'exceptionCode' => $e->getCode(),
                            'user' => $userIdentifier
                        ]
                    )
            );
            $entityManager->flush();
            return new Response('Invalid hook payload. See https://intercept.typo3.com for more information.', Response::HTTP_PRECONDITION_FAILED);
        } catch (DocsPackageDoNotCareBranch $e) {
            $entityManager->persist(
                (new HistoryEntry())
                    ->setType(HistoryEntryType::DOCS_RENDERING)
                    ->setStatus(DocsRenderingHistoryStatus::NO_RELEVANT_BRANCH_OR_TAG)
                    ->setData(
                        [
                            'type' => HistoryEntryType::DOCS_RENDERING,
                            'status' => DocsRenderingHistoryStatus::NO_RELEVANT_BRANCH_OR_TAG,
                            'triggeredBy' => HistoryEntryTrigger::API,
                            'exceptionCode' => $e->getCode(),
                            'exceptionMessage' => $e->getMessage(),
                            'repository' => $documentationJar->getRepositoryUrl(),
                            'sourceBranch' => $documentationJar->getBranch(),
                            'user' => $userIdentifier
                        ]
                    )
            );
            $entityManager->flush();
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
