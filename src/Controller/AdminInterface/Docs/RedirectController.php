<?php

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
use App\Form\DocsServerRedirectType;
use App\Form\RedirectFilterType;
use App\Repository\DocsServerRedirectRepository;
use App\Repository\HistoryEntryRepository;
use App\Service\DocsServerNginxService;
use App\Service\GithubService;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use T3G\Bundle\Keycloak\Security\KeyCloakUser;

/**
 * @Route("/redirect")
 */
class RedirectController extends AbstractController
{
    protected DocsServerNginxService $nginxService;

    protected GithubService $githubService;

    private Security $security;

    public function __construct(
        DocsServerNginxService $nginxService,
        GithubService $githubService,
        Security $security
    )
    {
        $this->nginxService = $nginxService;
        $this->githubService = $githubService;
        $this->security = $security;
    }

    /**
     * @Route("/", name="admin_redirect_index", methods={"GET"})
     * @IsGranted("ROLE_DOCUMENTATION_MAINTAINER")
     * @param DocsServerRedirectRepository $redirectRepository
     * @param HistoryEntryRepository $historyEntryRepository
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return Response
     */
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
        $sortDirection = $requestSortDirection === 'asc' ? Criteria::ASC : Criteria::DESC;
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
                'filter' => $form->createView(),
                'staticConfiguration' => $staticConfigurationFile,
            ]
        );
    }

    /**
     * @Route("/new", name="admin_redirect_new", methods={"GET","POST"})
     * @IsGranted("ROLE_DOCUMENTATION_MAINTAINER")
     * @param Request $request
     * @return Response
     */
    public function new(
        Request $request
    ): Response {
        $redirect = new DocsServerRedirect();
        $form = $this->createForm(DocsServerRedirectType::class, $redirect);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($redirect);
            $entityManager->flush();

            $this->createRedirectsAndDeploy('new', $redirect);
            return $this->redirectToRoute('admin_redirect_index');
        }

        return $this->render(
            'docs_redirect/new.html.twig',
            [
                'redirect' => $redirect,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id<\d+>}", name="admin_redirect_show", methods={"GET"})
     * @IsGranted("ROLE_DOCUMENTATION_MAINTAINER")
     * @param DocsServerRedirect $redirect
     * @return Response
     */
    public function show(
        DocsServerRedirect $redirect
    ): Response {
        return $this->render(
            'docs_redirect/show.html.twig',
            [
                'redirect' => $redirect
            ]
        );
    }

    /**
     * @Route("/{id<\d+>}/edit", name="admin_redirect_edit", methods={"GET","POST"})
     * @IsGranted("ROLE_DOCUMENTATION_MAINTAINER")
     * @param Request $request
     * @param DocsServerRedirect $redirect
     * @return Response
     */
    public function edit(
        Request $request,
        DocsServerRedirect $redirect
    ): Response {
        $form = $this->createForm(DocsServerRedirectType::class, $redirect);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            $this->createRedirectsAndDeploy('edit', $redirect);
            return $this->redirectToRoute('admin_redirect_index', ['id' => $redirect->getId()]);
        }

        return $this->render(
            'docs_redirect/edit.html.twig',
            [
                'redirect' => $redirect,
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/{id<\d+>}", name="admin_redirect_delete", methods={"DELETE"})
     * @IsGranted("ROLE_DOCUMENTATION_MAINTAINER")
     * @param Request $request
     * @param DocsServerRedirect $redirect
     * @return Response
     */
    public function delete(
        Request $request,
        DocsServerRedirect $redirect
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $redirect->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($redirect);
            $entityManager->flush();
            $this->createRedirectsAndDeploy('delete', $redirect);
        }

        return $this->redirectToRoute('admin_redirect_index');
    }

    /**
     * @Route("/dynamic", name="admin_redirect_dynamic", methods={"GET"})
     * @return Response
     */
    public function getDynamicRedirects(): Response
    {
        return new Response(implode(chr(10), $this->nginxService->getDynamicConfiguration()));
    }

    /**
     * @Route("/static", name="admin_redirect_static", methods={"GET"})
     * @return Response
     */
    public function getStaticRedirects(): Response
    {
        return new Response($this->nginxService->getStaticConfiguration()->getContents());
    }

    /**
     * @param string $triggeredBySubType
     * @param DocsServerRedirect $redirect
     */
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
        $manager = $this->getDoctrine()->getManager();
        $manager->persist(
            (new HistoryEntry())
                ->setType(HistoryEntryType::DOCS_REDIRECT)
                ->setStatus(DocsRenderingHistoryStatus::TRIGGERED)
                ->setGroupEntry($bambooBuildTriggered->buildResultKey)
                ->setData(
                    [
                        'type' => HistoryEntryType::DOCS_REDIRECT,
                        'status' => DocsRenderingHistoryStatus::TRIGGERED,
                        'triggeredBy' => HistoryEntryTrigger::WEB,
                        'subType' => $triggeredBySubType,
                        'redirect' => $redirect->toArray(),
                        'bambooKey' => $bambooBuildTriggered->buildResultKey,
                        'user' => $userIdentifier
                    ]
                )
        );
        $manager->flush();
    }
}
