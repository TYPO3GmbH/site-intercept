<?php

namespace App\Controller;

use App\Entity\Redirect;
use App\Form\RedirectType;
use App\Repository\RedirectRepository;
use App\Security\RedirectVoter;
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
     */
    public function index(RedirectRepository $redirectRepository): Response
    {
        return $this->render('redirect/index.html.twig', ['redirects' => $redirectRepository->findAll()]);
    }

    /**
     * @Route("/new", name="redirect_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $redirect = new Redirect();
        $form = $this->createForm(RedirectType::class, $redirect);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($redirect);
            $entityManager->flush();

            $this->updateAndReloadNginx();
            return $this->redirectToRoute('redirect_index');
        }

        return $this->render('redirect/new.html.twig', [
            'redirect' => $redirect,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="redirect_show", methods={"GET"})
     */
    public function show(Redirect $redirect): Response
    {
        $this->denyAccessUnlessGranted(RedirectVoter::VIEW, $redirect);
        return $this->render('redirect/show.html.twig', ['redirect' => $redirect]);
    }

    /**
     * @Route("/{id}/edit", name="redirect_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Redirect $redirect): Response
    {
        $this->denyAccessUnlessGranted(RedirectVoter::EDIT, $redirect);
        $form = $this->createForm(RedirectType::class, $redirect);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            $this->updateAndReloadNginx();
            return $this->redirectToRoute('redirect_index', ['id' => $redirect->getId()]);
        }

        return $this->render('redirect/edit.html.twig', [
            'redirect' => $redirect,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="redirect_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Redirect $redirect): Response
    {
        $this->denyAccessUnlessGranted(RedirectVoter::DELETE, $redirect);
        if ($this->isCsrfTokenValid('delete'.$redirect->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($redirect);
            $entityManager->flush();
            $this->updateAndReloadNginx();
        }

        return $this->redirectToRoute('redirect_index');
    }

    protected function updateAndReloadNginx()
    {
        $this->nginxService->createNewConfigAndReload();
    }
}
