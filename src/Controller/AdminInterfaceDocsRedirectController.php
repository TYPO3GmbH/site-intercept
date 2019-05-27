<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\DocsServerRedirect;
use App\Form\DocsServerRedirectType;
use App\Form\RedirectFilterType;
use App\Repository\DocsServerRedirectRepository;
use App\Service\BambooService;
use App\Service\DocsServerNginxService;
use App\Service\GraylogService;
use Doctrine\Common\Collections\Criteria;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/redirect")
 */
class AdminInterfaceDocsRedirectController extends AbstractController
{
    /**
     * @var DocsServerNginxService
     */
    protected $nginxService;

    /**
     * @var BambooService
     */
    protected $bambooService;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(DocsServerNginxService $nginxService, BambooService $bambooService, LoggerInterface $logger)
    {
        $this->nginxService = $nginxService;
        $this->bambooService = $bambooService;
        $this->logger = $logger;
    }

    /**
     * @Route("/", name="admin_redirect_index", methods={"GET"})
     * @param DocsServerRedirectRepository $redirectRepository
     * @param GraylogService $graylogService
     * @return Response
     */
    public function index(DocsServerRedirectRepository $redirectRepository, GraylogService $graylogService, Request $request, PaginatorInterface $paginator): Response
    {
        $currentConfigurationFile = $this->nginxService->findCurrentConfiguration();
        $recentLogsMessages = $graylogService->getRecentRedirectActions();

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

        return $this->render('redirect/index.html.twig', [
            'redirects' => $redirects,
            'currentConfiguration' => $currentConfigurationFile,
            'logMessages' => $recentLogsMessages,
            'pagination' => $pagination,
            'filter' => $form->createView(),
        ]);
    }

    /**
     * @Route("/new", name="admin_redirect_new", methods={"GET","POST"})
     * @param Request $request
     * @return Response
     */
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_DOCUMENTATION_MAINTAINER');
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

        return $this->render('redirect/new.html.twig', [
            'redirect' => $redirect,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="admin_redirect_show", methods={"GET"})
     * @param DocsServerRedirect $redirect
     * @return Response
     */
    public function show(DocsServerRedirect $redirect): Response
    {
        $this->denyAccessUnlessGranted('ROLE_DOCUMENTATION_MAINTAINER');
        return $this->render('redirect/show.html.twig', ['redirect' => $redirect]);
    }

    /**
     * @Route("/{id}/edit", name="admin_redirect_edit", methods={"GET","POST"})
     * @param Request $request
     * @param DocsServerRedirect $redirect
     * @return Response
     */
    public function edit(Request $request, DocsServerRedirect $redirect): Response
    {
        $this->denyAccessUnlessGranted('ROLE_DOCUMENTATION_MAINTAINER');
        $form = $this->createForm(DocsServerRedirectType::class, $redirect);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            $this->createRedirectsAndDeploy('edit', $redirect);
            return $this->redirectToRoute('admin_redirect_index', ['id' => $redirect->getId()]);
        }

        return $this->render('redirect/edit.html.twig', [
            'redirect' => $redirect,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="admin_redirect_delete", methods={"DELETE"})
     * @param Request $request
     * @param DocsServerRedirect $redirect
     * @return Response
     */
    public function delete(Request $request, DocsServerRedirect $redirect): Response
    {
        $this->denyAccessUnlessGranted('ROLE_DOCUMENTATION_MAINTAINER');
        if ($this->isCsrfTokenValid('delete' . $redirect->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($redirect);
            $entityManager->flush();
            $this->createRedirectsAndDeploy('delete', $redirect);
        }

        return $this->redirectToRoute('admin_redirect_index');
    }

    /**
     * @param string $triggeredBySubType
     * @param DocsServerRedirect $redirect
     */
    protected function createRedirectsAndDeploy(string $triggeredBySubType, DocsServerRedirect $redirect): void
    {
        $filename = $this->nginxService->createRedirectConfigFile();
        $bambooBuildTriggered = $this->bambooService->triggerDocumentationRedirectsPlan(basename($filename));

        $this->logger->info(
            'Triggered redirects deployment',
            [
                'type' => 'docsRedirect',
                'status' => 'triggered',
                'triggeredBy' => 'interface',
                'subType' => $triggeredBySubType,
                'redirect' => $redirect->toArray(),
                'bambooKey' => $bambooBuildTriggered->buildResultKey,
            ]
        );
    }
}
