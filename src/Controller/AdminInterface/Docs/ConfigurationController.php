<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller\AdminInterface\Docs;

use App\Entity\DocumentationJar;
use App\Exception\Composer\DocsComposerDependencyException;
use App\Exception\Composer\DocsComposerMissingValueException;
use App\Exception\ComposerJsonInvalidException;
use App\Exception\ComposerJsonNotFoundException;
use App\Exception\DocsPackageDoNotCareBranch;
use App\Exception\DocsPackageRegisteredWithDifferentRepositoryException;
use App\Form\DocumentationDeployment;
use App\Repository\RepositoryBlacklistEntryRepository;
use App\Service\DocumentationBuildInformationService;
use App\Service\DocumentationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ConfigurationController extends AbstractController
{
    #[Route(path: '/admin/docs/deployments/add', name: 'admin_docs_deployments_add')]
    #[IsGranted('ROLE_DOCUMENTATION_MAINTAINER')]
    public function addConfiguration(Request $request, RepositoryBlacklistEntryRepository $repositoryBlacklistEntryRepository): Response
    {
        $documentationJar = new DocumentationJar();
        $repositoryUrl = $request->get('documentation_deployment')['repositoryUrl'] ?? '';
        $documentationJar->setRepositoryUrl($repositoryUrl);

        $form = $this->createForm(DocumentationDeployment::class, $documentationJar);
        $form->get('repositoryType')->setData($request->get('documentation_deployment')['repositoryType'] ?? '');
        $form->handleRequest($request);
        if (!empty($repositoryUrl)) {
            if (1 !== preg_match(DocumentationJar::VALID_REPOSITORY_URL_REGEX, (string) $repositoryUrl)) {
                $error = new FormError('A repository url must be a valid https git url. Staring with \'https://\' and ending with \'.git\'.');
                $form->addError($error);
            } elseif ($repositoryBlacklistEntryRepository->isBlacklisted($repositoryUrl)) {
                $error = new FormError('This repository has been blacklisted and cannot be added.');
                $form->addError($error);
            } else {
                return $this->forward(self::class . '::addConfigurationStep2');
            }
        }

        return $this->render(
            'docs_configuration/form.html.twig',
            [
                'redirect' => $documentationJar,
                'form' => $form,
                'showPrev' => false,
            ]
        );
    }

    #[Route(path: '/admin/docs/deployments/add/step2', name: 'admin_docs_deployments_add_step2')]
    #[IsGranted('ROLE_DOCUMENTATION_MAINTAINER')]
    public function addConfigurationStep2(Request $request): Response
    {
        $documentationJar = new DocumentationJar();

        $repositoryUrl = $request->get('documentation_deployment')['repositoryUrl'] ?? '';
        $documentationJar->setRepositoryUrl($repositoryUrl);
        $branch = $request->get('documentation_deployment')['branch'] ?? '';
        $documentationJar->setBranch($branch);

        $form = $this->createForm(DocumentationDeployment::class, $documentationJar, ['step2' => true]);
        $form->setData($documentationJar);
        $form->get('repositoryType')->setData($request->get('documentation_deployment')['repositoryType']);
        if ('' !== $branch) {
            return $this->forward(self::class . '::addConfigurationStep3');
        }

        return $this->render(
            'docs_configuration/form.html.twig',
            [
                'redirect' => $documentationJar,
                'form' => $form,
                'showPrev' => true,
            ]
        );
    }

    /**
     * @throws DocsPackageDoNotCareBranch
     * @throws DocsComposerMissingValueException
     */
    #[Route(path: '/admin/docs/deployments/add/step3', name: 'admin_docs_deployments_add_step3')]
    #[IsGranted('ROLE_DOCUMENTATION_MAINTAINER')]
    public function addConfigurationStep3(
        Request $request,
        DocumentationBuildInformationService $docBuildInfoService,
        DocumentationService $docService
    ): Response {
        $deploymentParams = $request->get('documentation_deployment');
        $repositoryUrl = $deploymentParams['repositoryUrl'];
        $branch = $deploymentParams['branch'] ?? '';
        $publicComposerJsonUrl = $deploymentParams['publicComposerJsonUrl'] ?? '';

        $doc = new DocumentationJar();
        $doc->setRepositoryUrl($repositoryUrl ?? '');
        $doc->setBranch($branch);
        $doc->setPublicComposerJsonUrl($publicComposerJsonUrl);

        $deploymentInformation = null;
        try {
            $docService->enrichWithComposerInformation($doc, $branch);
            // pre-fill form composer json url
            $deploymentParams['publicComposerJsonUrl'] = $doc->getPublicComposerJsonUrl();
            $request->request->set('documentation_deployment', $deploymentParams);
            $docService->assertUrlIsUnique($repositoryUrl, $doc->getPackageName());
            $deploymentInformation = $docBuildInfoService->generateBuildInformationFromDocumentationJar($doc);
        } catch (ComposerJsonNotFoundException|ComposerJsonInvalidException|DocsComposerDependencyException|DocsPackageDoNotCareBranch|DocsPackageRegisteredWithDifferentRepositoryException $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        $form = $this->createForm(DocumentationDeployment::class, $doc, ['step3' => true]);
        $form->handleRequest($request);
        if (null !== $deploymentInformation && $form->isValid()) {
            $docService->addNewDocumentationBuild($doc, $deploymentInformation);
            $this->addFlash('success', 'Configuration added');

            return $this->redirectToRoute('admin_docs_deployments');
        }

        return $this->render(
            'docs_configuration/form.html.twig',
            [
                'redirect' => $doc,
                'form' => $form,
                'showPrev' => true,
            ]
        );
    }
}
