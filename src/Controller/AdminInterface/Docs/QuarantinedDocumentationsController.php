<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller\AdminInterface\Docs;

use App\Entity\DocumentationQuarantine;
use App\Entity\KnownRepositoryDomain;
use App\Enum\DocumentationRenderingTrigger;
use App\Enum\RepositoryDomainStatus;
use App\Form\QuarantinedDocumentationAllowType;
use App\Form\QuarantinedDocumentationDisallowType;
use App\Repository\DocumentationQuarantineRepository;
use App\Service\DocumentationQuarantineService;
use App\Service\RenderDocumentationService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/admin/docs/quatantine', name: 'admin_docs_quarantine_')]
final class QuarantinedDocumentationsController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DocumentationQuarantineRepository $documentationQuarantineRepository,
        private readonly DocumentationQuarantineService $documentationQuarantineService,
        private readonly RenderDocumentationService $renderDocumentationService,
        private readonly PaginatorInterface $paginator,
    ) {
    }

    #[Route(path: '/', name: 'index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(Request $request): Response
    {
        $pagination = $this->paginator->paginate(
            $this->documentationQuarantineRepository->findAll(),
            $request->query->getInt('page', 1)
        );

        return $this->render('admin/docs/quarantine/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route(path: '/{quarantinedDocumentation}/allow', name: 'allow', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function allow(Request $request, DocumentationQuarantine $quarantinedDocumentation): Response
    {
        $form = $this->createForm(QuarantinedDocumentationAllowType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $domain = $quarantinedDocumentation->getDomain();

            $knownRepositoryDomain = (new KnownRepositoryDomain())
                ->setDomain($domain)
                ->setStatus(RepositoryDomainStatus::ALLOWED);
            $this->entityManager->persist($knownRepositoryDomain);
            $this->entityManager->flush();

            foreach ($this->documentationQuarantineService->findAllByDomain($domain) as $documentationQuarantine) {
                $pushEvent = $documentationQuarantine->getPushEvent();
                $this->renderDocumentationService->requestDocumentationRendering($pushEvent, DocumentationRenderingTrigger::WEB);

                $this->entityManager->remove($documentationQuarantine);
            }
            $this->entityManager->flush();

            $this->addFlash('success', sprintf('The domain %s has been allowed and all quarantined renderings have been activated.', $domain));

            return $this->redirectToRoute('admin_docs_quarantine_index');
        }

        return $this->render(
            'admin/docs/quarantine/allow.html.twig',
            [
                'form' => $form,
                'quarantinedDocumentation' => $quarantinedDocumentation,
            ]
        );
    }

    #[Route(path: '/{quarantinedDocumentation}/disallow', name: 'disallow', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function disallow(Request $request, DocumentationQuarantine $quarantinedDocumentation): Response
    {
        $form = $this->createForm(QuarantinedDocumentationDisallowType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $domain = $quarantinedDocumentation->getDomain();

            $knownRepositoryDomain = (new KnownRepositoryDomain())
                ->setDomain($domain)
                ->setStatus(RepositoryDomainStatus::DISALLOWED);
            $this->entityManager->persist($knownRepositoryDomain);
            $this->entityManager->flush();

            foreach ($this->documentationQuarantineService->findAllByDomain($domain) as $documentationQuarantine) {
                $this->entityManager->remove($documentationQuarantine);
            }
            $this->entityManager->flush();

            $this->addFlash('success', sprintf('The domain %s has been disallowed and all quarantined renderings have been removed.', $domain));

            return $this->redirectToRoute('admin_docs_quarantine_index');
        }

        return $this->render(
            'admin/docs/quarantine/disallow.html.twig',
            [
                'form' => $form,
                'quarantinedDocumentation' => $quarantinedDocumentation,
            ]
        );
    }
}
