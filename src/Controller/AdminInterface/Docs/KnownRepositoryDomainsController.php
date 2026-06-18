<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller\AdminInterface\Docs;

use App\Entity\KnownRepositoryDomain;
use App\Enum\DocumentationRenderingTrigger;
use App\Form\KnownDomainCreateType;
use App\Form\KnownDomainDeleteType;
use App\Repository\KnownRepositoryDomainRepository;
use App\Service\DocumentationQuarantineService;
use App\Service\RenderDocumentationService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/admin/docs/domains', name: 'admin_docs_domains_')]
final class KnownRepositoryDomainsController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly KnownRepositoryDomainRepository $knownRepositoryDomainsRepository,
        private readonly DocumentationQuarantineService $documentationQuarantineService,
        private readonly RenderDocumentationService $renderDocumentationService,
        private readonly PaginatorInterface $paginator,
        private readonly Security $security,
    ) {
    }

    #[Route(path: '/', name: 'index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(Request $request): Response
    {
        $pagination = $this->paginator->paginate(
            $this->knownRepositoryDomainsRepository->findAll(),
            $request->query->getInt('page', 1)
        );

        return $this->render('admin/docs/known_domains/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route(path: '/new', name: 'new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request): Response
    {
        $form = $this->createForm(KnownDomainCreateType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var KnownRepositoryDomain $data */
            $data = $form->getData();
            $this->entityManager->persist($data);
            $this->entityManager->flush();

            if ($data->isAllowed()) {
                foreach ($this->documentationQuarantineService->findAllByDomain($data->getDomain()) as $documentationQuarantine) {
                    $pushEvent = $documentationQuarantine->getPushEvent();
                    $this->renderDocumentationService->requestDocumentationRendering($pushEvent, DocumentationRenderingTrigger::WEB);

                    $this->entityManager->remove($documentationQuarantine);
                }
                $this->entityManager->flush();
            } elseif ($data->isDisallowed()) {
                foreach ($this->documentationQuarantineService->findAllByDomain($data->getDomain()) as $documentationQuarantine) {
                    $this->entityManager->remove($documentationQuarantine);
                }
                $this->entityManager->flush();
            }

            $this->addFlash('success', sprintf('The domain %s has been registered.', $data->getDomain()));

            return $this->redirectToRoute('admin_docs_domains_index');
        }

        return $this->render(
            'admin/docs/known_domains/new.html.twig',
            [
                'form' => $form,
            ]
        );
    }

    #[Route(path: '/{domain}/delete', name: 'delete', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, KnownRepositoryDomain $domain): Response
    {
        if ($domain->isLocked() && !$this->security->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', sprintf('%s is a locked domain, deletion is prohibited for non-admin users.', $domain->getDomain()));

            return $this->redirectToRoute('admin_docs_domains_index');
        }

        $form = $this->createForm(KnownDomainDeleteType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if (true === $data['delete']) {
                $this->entityManager->remove($domain);
                $this->entityManager->flush();
            }

            $this->addFlash('success', sprintf('The domain %s has been deleted.', $domain->getDomain()));

            return $this->redirectToRoute('admin_docs_domains_index');
        }

        return $this->render(
            'admin/docs/known_domains/delete.html.twig',
            [
                'knownDomain' => $domain,
                'form' => $form,
            ]
        );
    }
}
