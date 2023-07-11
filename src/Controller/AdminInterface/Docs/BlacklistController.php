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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BlacklistController extends AbstractController
{
    #[Route(path: '/admin/docs/deployments/blacklist/{documentationJarId}', name: 'admin_docs_deployments_blacklist_action', requirements: ['documentationJarId' => '\d+'])]
    #[IsGranted('ROLE_DOCUMENTATION_MAINTAINER')]
    public function blacklist(
        int $documentationJarId,
        DocumentationJarRepository $documentationJarRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $originalJar = $documentationJarRepository->find($documentationJarId);
        $jars = $documentationJarRepository->findBy(['repositoryUrl' => $originalJar->getRepositoryUrl()]);

        $blacklistEntry = new RepositoryBlacklistEntry();
        $blacklistEntry->setRepositoryUrl($originalJar->getRepositoryUrl());
        $entityManager->persist($blacklistEntry);

        foreach ($jars as $jar) {
            $entityManager->remove($jar);
        }
        $entityManager->flush();
        $this->addFlash('success', 'Repository has been blacklisted.');

        return $this->redirectToRoute('admin_docs_deployments');
    }

    #[Route(path: '/admin/docs/deployments/blacklist', name: 'admin_docs_deployments_blacklist_index')]
    #[IsGranted('ROLE_DOCUMENTATION_MAINTAINER')]
    public function blacklistIndex(
        Request $request,
        PaginatorInterface $paginator,
        RepositoryBlacklistEntryRepository $repositoryBlacklistEntryRepository
    ): Response {
        $entries = $repositoryBlacklistEntryRepository->findAll();

        $pagination = $paginator->paginate(
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
    public function blacklistDelete(
        int $entryId,
        RepositoryBlacklistEntryRepository $repositoryBlacklistEntryRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $entry = $repositoryBlacklistEntryRepository->find($entryId);

        if (null !== $entry) {
            $entityManager->remove($entry);
            $entityManager->flush();
            $this->addFlash('success', 'Blacklist entry deleted.');
        }

        return $this->redirectToRoute('admin_docs_deployments_blacklist_index');
    }
}
