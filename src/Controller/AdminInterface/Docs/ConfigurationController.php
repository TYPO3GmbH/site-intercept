<?php
declare(strict_types = 1);

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
use App\Repository\DocumentationJarRepository;
use App\Repository\RepositoryBlacklistEntryRepository;
use App\Service\BambooService;
use App\Service\DocumentationBuildInformationService;
use App\Service\DocumentationService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConfigurationController extends AbstractController
{
    /**
     * @Route("/admin/docs/deployments/add", name="admin_docs_deployments_add")
     * @IsGranted("ROLE_DOCUMENTATION_MAINTAINER")
     * @param Request $request
     * @param RepositoryBlacklistEntryRepository $repositoryBlacklistEntryRepository
     * @return Response
     */
    public function addConfiguration(Request $request, RepositoryBlacklistEntryRepository $repositoryBlacklistEntryRepository): Response
    {
        $documentationJar = new DocumentationJar();
        $repositoryUrl = $request->get('documentation_deployment')['repositoryUrl'] ?? '';
        $documentationJar->setRepositoryUrl($repositoryUrl);

        $form = $this->createForm(DocumentationDeployment::class, $documentationJar);
        $form->get('repositoryType')->setData($request->get('documentation_deployment')['repositoryType'] ?? '');
        $form->handleRequest($request);
        if (!empty($repositoryUrl)) {
            if (preg_match(DocumentationJar::VALID_REPOSITORY_URL_REGEX, $repositoryUrl) !== 1) {
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
                'form' => $form->createView(),
                'showPrev' => false,
            ]
        );
    }

    /**
     * @Route("/admin/docs/deployments/add/step2", name="admin_docs_deployments_add_step2")
     * @IsGranted("ROLE_DOCUMENTATION_MAINTAINER")
     * @param Request $request
     * @return Response
     */
    public function addConfigurationStep2(Request $request): Response
    {
        $documentationJar = new DocumentationJar();

        $repositoryUrl = $request->get('documentation_deployment')['repositoryUrl'] ?? '';
        $documentationJar->setRepositoryUrl($repositoryUrl);
        $branch = $request->get('documentation_deployment')['branch'] ?? '';
        $documentationJar->setBranch($branch);

        $form = $this->createForm(DocumentationDeployment::class, $documentationJar, ['step2' => true, 'entity_manager' => $this->getDoctrine()->getManager()]);
        $form->setData($documentationJar);
        $form->get('repositoryType')->setData($request->get('documentation_deployment')['repositoryType']);
        if ($branch !== '') {
            return $this->forward(self::class . '::addConfigurationStep3');
        }

        return $this->render(
            'docs_configuration/form.html.twig',
            [
                'redirect' => $documentationJar,
                'form' => $form->createView(),
                'showPrev' => true,
            ]
        );
    }

    /**
     * @Route("/admin/docs/deployments/add/step3", name="admin_docs_deployments_add_step3")
     * @IsGranted("ROLE_DOCUMENTATION_MAINTAINER")
     * @param Request $request
     * @param DocumentationBuildInformationService $docBuildInfoService
     * @param BambooService $bambooService
     * @param DocumentationJarRepository $docsRepository
     * @param DocumentationService $docService
     * @return Response
     * @throws DocsPackageDoNotCareBranch
     * @throws DocsComposerMissingValueException
     */
    public function addConfigurationStep3(
        Request $request,
        DocumentationBuildInformationService $docBuildInfoService,
        BambooService $bambooService,
        DocumentationJarRepository $docsRepository,
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
        } catch (ComposerJsonNotFoundException | ComposerJsonInvalidException | DocsComposerDependencyException | DocsPackageDoNotCareBranch | DocsPackageRegisteredWithDifferentRepositoryException $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        $form = $this->createForm(DocumentationDeployment::class, $doc, ['step3' => true]);
        $form->handleRequest($request);
        if ($deploymentInformation !== null && $form->isValid()) {
            $docService->addNewDocumentationBuild($doc, $deploymentInformation);
            $this->addFlash('success', 'Configuration added');
            return $this->redirectToRoute('admin_docs_deployments');
        }

        return $this->render(
            'docs_configuration/form.html.twig',
            [
                'redirect' => $doc,
                'form' => $form->createView(),
                'showPrev' => true,
            ]
        );
    }
}
