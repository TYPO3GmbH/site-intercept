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
use App\Repository\DocsServerRedirectRepository;
use App\Service\BambooService;
use App\Service\DocsServerNginxService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/redirect")
 */
class AdminInterfaceRedirectController extends AbstractController
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
     * @return Response
     */
    public function index(DocsServerRedirectRepository $redirectRepository): Response
    {
        $this->logger->info('Triggered: ' . __CLASS__ . '::' . __METHOD__, [
            'type' => 'docsRedirectIndex',
            'triggeredBy' => 'interface',
        ]);
        return $this->render('redirect/index.html.twig', ['redirects' => $redirectRepository->findAll()]);
    }

    /**
     * @Route("/get/{filename}", name="admin_redirect_get", methods={"GET"})
     * @param string $filename
     * @return Response
     */
    public function get(string $filename): Response
    {
        $this->logger->info('Triggered: ' . __CLASS__ . '::' . __METHOD__, [
            'type' => 'docsRedirectGet',
            'triggeredBy' => 'interface',
            'filename' => $filename,
        ]);
        return new Response($this->nginxService->getFileContent($filename));
    }

    /**
     * @Route("/new", name="admin_redirect_new", methods={"GET","POST"})
     * @param Request $request
     * @return Response
     */
    public function new(Request $request): Response
    {
        $this->logger->info('Triggered: ' . __CLASS__ . '::' . __METHOD__, [
            'type' => 'docsRedirectNew',
            'triggeredBy' => 'interface',
        ]);
        $this->denyAccessUnlessGranted('ROLE_DOCUMENTATION_MAINTAINER');
        $redirect = new DocsServerRedirect();
        $form = $this->createForm(DocsServerRedirectType::class, $redirect);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($redirect);
            $entityManager->flush();

            $this->createRedirectsAndDeploy();
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
        $this->logger->info('Triggered: ' . __CLASS__ . '::' . __METHOD__, [
            'type' => 'docsRedirectShow',
            'triggeredBy' => 'interface',
            'redirect' => $redirect,
        ]);
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
        $this->logger->info('Triggered: ' . __CLASS__ . '::' . __METHOD__, [
            'type' => 'docsRedirectEdit',
            'triggeredBy' => 'interface',
            'redirect' => $redirect,
        ]);
        $this->denyAccessUnlessGranted('ROLE_DOCUMENTATION_MAINTAINER');
        $form = $this->createForm(DocsServerRedirectType::class, $redirect);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            $this->createRedirectsAndDeploy();
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
        $this->logger->info('Triggered: ' . __CLASS__ . '::' . __METHOD__, [
            'type' => 'docsRedirectDelete',
            'triggeredBy' => 'interface',
            'redirect' => $redirect,
        ]);
        $this->denyAccessUnlessGranted('ROLE_DOCUMENTATION_MAINTAINER');
        if ($this->isCsrfTokenValid('delete' . $redirect->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($redirect);
            $entityManager->flush();
            $this->createRedirectsAndDeploy();
        }

        return $this->redirectToRoute('admin_redirect_index');
    }

    protected function createRedirectsAndDeploy(): void
    {
        $filename = $this->nginxService->createRedirectConfigFile();
        $this->bambooService->triggerDocumentationRedirectsPlan(basename($filename));
    }
}
