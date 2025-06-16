<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller\AdminInterface\Docs;

use App\Entity\DocsServerRedirect;
use App\Entity\HistoryEntry;
use App\Enum\DocsRenderingHistoryStatus;
use App\Enum\HistoryEntryTrigger;
use App\Enum\HistoryEntryType;
use App\Form\DeleteRedirectType;
use App\Form\DocsServerRedirectType;
use App\Form\RedirectFilterType;
use App\Repository\DocsServerRedirectRepository;
use App\Repository\HistoryEntryRepository;
use App\Service\DocsServerNginxService;
use App\Service\GithubService;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Order;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use T3G\Bundle\Keycloak\Security\KeyCloakUser;

#[Route(path: '/redirect')]
class RedirectController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DocsServerNginxService $nginxService,
        private readonly GithubService $githubService,
        private readonly Security $security,
    ) {
    }

    #[Route(path: '/', name: 'admin_redirect_index', methods: ['GET'])]
    #[IsGranted('ROLE_DOCUMENTATION_MAINTAINER')]
    public function index(
        DocsServerRedirectRepository $redirectRepository,
        HistoryEntryRepository $historyEntryRepository,
        Request $request,
        PaginatorInterface $paginator
    ): Response {
        $currentConfigurationFile = $this->nginxService->getDynamicConfiguration();
        $staticConfigurationFile = $this->nginxService->getStaticConfiguration();
        $recentLogsMessages = $historyEntryRepository->findByType(HistoryEntryType::DOCS_REDIRECT);

        $criteria = Criteria::create();

        $requestSortDirection = $request->query->get('direction');
        $requestSortField = $request->query->get('sort');
        $sortDirection = 'asc' === $requestSortDirection ? Order::Ascending : Order::Descending;
        $sortField = in_array($requestSortField, ['source', 'target']) ? $requestSortField : 'target';
        $criteria->orderBy([$sortField => $sortDirection]);

        $form = $this->createForm(RedirectFilterType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $expressionBuilder = Criteria::expr();
            $data = $form->getData();
            if ($data['search']) {
                $criteria->where($expressionBuilder->contains('source', $data['search']))
                ->orWhere($expressionBuilder->contains('target', $data['search']));
            }
        }

        $redirects = $redirectRepository->matching($criteria);

        $pagination = $paginator->paginate(
            $redirects,
            $request->query->getInt('page', 1)
        );

        return $this->render(
            'docs_redirect/index.html.twig',
            [
                'currentConfiguration' => $currentConfigurationFile,
                'logMessages' => $recentLogsMessages,
                'pagination' => $pagination,
                'filter' => $form,
                'staticConfiguration' => $staticConfigurationFile,
            ]
        );
    }

    #[Route(path: '/new', name: 'admin_redirect_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_DOCUMENTATION_MAINTAINER')]
    public function new(
        Request $request
    ): Response {
        $redirect = new DocsServerRedirect();
        $form = $this->createForm(DocsServerRedirectType::class, $redirect);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($redirect);
            $this->entityManager->flush();

            $this->createRedirectsAndDeploy('new', $redirect);

            return $this->redirectToRoute('admin_redirect_index');
        }

        return $this->render(
            'docs_redirect/new.html.twig',
            [
                'redirect' => $redirect,
                'form' => $form,
            ]
        );
    }

    #[Route(path: '/{id<\d+>}', name: 'admin_redirect_show', methods: ['GET'])]
    #[IsGranted('ROLE_DOCUMENTATION_MAINTAINER')]
    public function show(
        DocsServerRedirect $redirect
    ): Response {
        $deleteRedirectForm = $this->createForm(DeleteRedirectType::class, [], [
            'action' => $this->generateUrl('admin_redirect_delete', ['id' => $redirect->getId()]),
        ]);

        return $this->render(
            'docs_redirect/show.html.twig',
            [
                'redirect' => $redirect,
                'deleteForm' => $deleteRedirectForm,
            ]
        );
    }

    #[Route(path: '/{id<\d+>}/edit', name: 'admin_redirect_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_DOCUMENTATION_MAINTAINER')]
    public function edit(
        Request $request,
        DocsServerRedirect $redirect
    ): Response {
        $deleteRedirectForm = $this->createForm(DeleteRedirectType::class, [], [
            'action' => $this->generateUrl('admin_redirect_delete', ['id' => $redirect->getId()]),
        ]);
        $form = $this->createForm(DocsServerRedirectType::class, $redirect);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->createRedirectsAndDeploy('edit', $redirect);

            return $this->redirectToRoute('admin_redirect_index', ['id' => $redirect->getId()]);
        }

        return $this->render(
            'docs_redirect/edit.html.twig',
            [
                'redirect' => $redirect,
                'form' => $form,
                'deleteForm' => $deleteRedirectForm,
            ]
        );
    }

    #[Route(path: '/{id<\d+>}', name: 'admin_redirect_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_DOCUMENTATION_MAINTAINER')]
    public function delete(
        Request $request,
        DocsServerRedirect $redirect,
    ): Response {
        $form = $this->createForm(DeleteRedirectType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->remove($redirect);
            $this->entityManager->flush();
            $this->createRedirectsAndDeploy('delete', $redirect);
        }

        return $this->redirectToRoute('admin_redirect_index');
    }

    #[Route(path: '/dynamic', name: 'admin_redirect_dynamic', methods: ['GET'])]
    public function getDynamicRedirects(): Response
    {
        return new Response(implode(chr(10), $this->nginxService->getDynamicConfiguration()));
    }

    #[Route(path: '/static', name: 'admin_redirect_static', methods: ['GET'])]
    public function getStaticRedirects(): Response
    {
        return new Response($this->nginxService->getStaticConfiguration()->getContents());
    }

    protected function createRedirectsAndDeploy(
        string $triggeredBySubType,
        DocsServerRedirect $redirect
    ): void {
        $bambooBuildTriggered = $this->githubService->triggerDocumentationRedirectsPlan();
        $user = $this->security->getUser();
        $userIdentifier = 'Anon.';
        if ($user instanceof KeyCloakUser) {
            $userIdentifier = $user->getDisplayName();
        }
        $this->entityManager->persist(
            (new HistoryEntry())
                ->setType(HistoryEntryType::DOCS_REDIRECT)
                ->setStatus(DocsRenderingHistoryStatus::TRIGGERED)
                ->setGroupEntry($bambooBuildTriggered->buildResultKey)
                ->setData([
                    'type' => HistoryEntryType::DOCS_REDIRECT,
                    'status' => DocsRenderingHistoryStatus::TRIGGERED,
                    'triggeredBy' => HistoryEntryTrigger::WEB,
                    'subType' => $triggeredBySubType,
                    'redirect' => $redirect->toArray(),
                    'bambooKey' => $bambooBuildTriggered->buildResultKey,
                    'user' => $userIdentifier,
                ])
        );
        $this->entityManager->flush();
    }
}
