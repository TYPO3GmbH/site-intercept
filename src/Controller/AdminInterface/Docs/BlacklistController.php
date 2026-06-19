<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller\AdminInterface\Docs;

use App\Entity\RepositoryBlacklistEntry;
use App\Repository\DocumentationJarRepository;
use App\Repository\RepositoryBlacklistEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class BlacklistController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DocumentationJarRepository $documentationJarRepository,
        private readonly RepositoryBlacklistEntryRepository $repositoryBlacklistEntryRepository,
        private readonly PaginatorInterface $paginator,
    ) {
    }

    #[Route(path: '/admin/docs/deployments/blacklist/{documentationJarId}', name: 'admin_docs_deployments_blacklist_action', requirements: ['documentationJarId' => '\d+'])]
    #[IsGranted('ROLE_DOCUMENTATION_MAINTAINER')]
    public function blacklist(int $documentationJarId): Response
    {
        $originalJar = $this->documentationJarRepository->find($documentationJarId);
        $jars = $this->documentationJarRepository->findBy(['repositoryUrl' => $originalJar->getRepositoryUrl()]);

        $blacklistEntry = new RepositoryBlacklistEntry();
        $blacklistEntry->setRepositoryUrl($originalJar->getRepositoryUrl());
        $this->entityManager->persist($blacklistEntry);

        foreach ($jars as $jar) {
            $this->entityManager->remove($jar);
        }
        $this->entityManager->flush();
        $this->addFlash('success', 'Repository has been blacklisted.');

        return $this->redirectToRoute('admin_docs_deployments');
    }

    #[Route(path: '/admin/docs/deployments/blacklist', name: 'admin_docs_deployments_blacklist_index')]
    #[IsGranted('ROLE_DOCUMENTATION_MAINTAINER')]
    public function blacklistIndex(Request $request): Response
    {
        $entries = $this->repositoryBlacklistEntryRepository->findAll();

        $pagination = $this->paginator->paginate(
            $entries,
            $request->query->getInt('page', 1)
        );

        return $this->render(
            'docs_blacklist/index.html.twig',
            [
                'pagination' => $pagination,
            ]
        );
    }

    #[Route(path: '/admin/docs/deployments/blacklist/delete/{entryId}', name: 'admin_docs_deployments_blacklist_delete_action', requirements: ['entryId' => '\d+'])]
    #[IsGranted('ROLE_DOCUMENTATION_MAINTAINER')]
    public function blacklistDelete(int $entryId): Response
    {
        $entry = $this->repositoryBlacklistEntryRepository->find($entryId);

        if (null !== $entry) {
            $this->entityManager->remove($entry);
            $this->entityManager->flush();
            $this->addFlash('success', 'Blacklist entry deleted.');
        }

        return $this->redirectToRoute('admin_docs_deployments_blacklist_index');
    }
}
