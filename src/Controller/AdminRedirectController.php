<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\Redirect;
use App\Form\RedirectType;
use App\Repository\RedirectRepository;
use App\Service\NginxService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/redirect")
 */
class AdminRedirectController extends AbstractController
{
    /**
     * @var NginxService
     */
    protected $nginxService;

    public function __construct(NginxService $nginxService)
    {
        $this->nginxService = $nginxService;
    }

    /**
     * @Route("/", name="redirect_index", methods={"GET"})
     * @param RedirectRepository $redirectRepository
     * @return Response
     */
    public function index(RedirectRepository $redirectRepository): Response
    {
        return $this->render('redirect/index.html.twig', ['redirects' => $redirectRepository->findAll()]);
    }

    /**
     * @Route("/get/{filename}", name="redirect_get", methods={"GET"})
     * @param string $filename
     * @return Response
     */
    public function get(string $filename): Response
    {
        return new Response($this->nginxService->getFileContent($filename));
    }

    /**
     * @Route("/new", name="redirect_new", methods={"GET","POST"})
     * @param Request $request
     * @return Response
     */
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_DOCUMENTATION_MAINTAINER');
        $redirect = new Redirect();
        $form = $this->createForm(RedirectType::class, $redirect);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($redirect);
            $entityManager->flush();

            $this->createRedirectsAndDeploy();
            return $this->redirectToRoute('redirect_index');
        }

        return $this->render('redirect/new.html.twig', [
            'redirect' => $redirect,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="redirect_show", methods={"GET"})
     * @param Redirect $redirect
     * @return Response
     */
    public function show(Redirect $redirect): Response
    {
        $this->denyAccessUnlessGranted('ROLE_DOCUMENTATION_MAINTAINER');
        return $this->render('redirect/show.html.twig', ['redirect' => $redirect]);
    }

    /**
     * @Route("/{id}/edit", name="redirect_edit", methods={"GET","POST"})
     * @param Request $request
     * @param Redirect $redirect
     * @return Response
     */
    public function edit(Request $request, Redirect $redirect): Response
    {
        $this->denyAccessUnlessGranted('ROLE_DOCUMENTATION_MAINTAINER');
        $form = $this->createForm(RedirectType::class, $redirect);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            $this->createRedirectsAndDeploy();
            return $this->redirectToRoute('redirect_index', ['id' => $redirect->getId()]);
        }

        return $this->render('redirect/edit.html.twig', [
            'redirect' => $redirect,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="redirect_delete", methods={"DELETE"})
     * @param Request $request
     * @param Redirect $redirect
     * @return Response
     */
    public function delete(Request $request, Redirect $redirect): Response
    {
        $this->denyAccessUnlessGranted('ROLE_DOCUMENTATION_MAINTAINER');
        if ($this->isCsrfTokenValid('delete' . $redirect->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($redirect);
            $entityManager->flush();
            $this->createRedirectsAndDeploy();
        }

        return $this->redirectToRoute('redirect_index');
    }

    protected function createRedirectsAndDeploy(): void
    {
        $filename = $this->nginxService->createRedirectConfigFile();
        $this->nginxService->createDeploymentJob($filename);
    }
}
