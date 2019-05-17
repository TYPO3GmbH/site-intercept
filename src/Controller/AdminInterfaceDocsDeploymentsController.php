<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller;

use App\Entity\DocumentationJar;
use App\Enum\DocumentationStatus;
use App\Exception\Composer\DocsComposerDependencyException;
use App\Exception\ComposerJsonInvalidException;
use App\Exception\ComposerJsonNotFoundException;
use App\Exception\DocsPackageDoNotCareBranch;
use App\Extractor\ComposerJson;
use App\Form\DocumentationDeployment;
use App\Repository\DocumentationJarRepository;
use App\Service\BambooService;
use App\Service\DocumentationBuildInformationService;
use App\Service\GitRepositoryService;
use App\Service\GraylogService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Show and manipulate all docs deployments managed by intercept
 */
class AdminInterfaceDocsDeploymentsController extends AbstractController
{
    /**
     * @Route("/admin/docs/deployments", name="admin_docs_deployments")
     *
     * @param BambooService $bambooService
     * @param GraylogService $graylogService
     * @param DocumentationJarRepository $documentationJarRepository
     * @return Response
     */
    public function index(
        BambooService $bambooService,
        GraylogService $graylogService,
        DocumentationJarRepository $documentationJarRepository
    ): Response {
        $recentLogsMessages = $graylogService->getRecentBambooDocsActions();
        $deployments = $documentationJarRepository->findAll();

        return $this->render(
            'docsDeployments.html.twig',
            [
                'logMessages' => $recentLogsMessages,
                'deployments' => $deployments,
                'bambooStatus' => $bambooService->getBambooStatus(),
                'docsLiveServer' => getenv('DOCS_LIVE_SERVER'),
            ]
        );
    }

    /**
     * @Route("/admin/docs/deployments/delete/{documentationJarId}/confirm", name="admin_docs_deployments_delete_view", requirements={"documentationJarId"="\d+"}, methods={"GET"})
     *
     * @param int $documentationJarId
     * @param DocumentationJarRepository $documentationJarRepository
     * @return Response
     */
    public function deleteConfirm(int $documentationJarId, DocumentationJarRepository $documentationJarRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_DOCUMENTATION_MAINTAINER');

        $jar = $documentationJarRepository->find($documentationJarId);
        if (null === $jar || !$jar->isDeletable()) {
            return $this->redirectToRoute('admin_docs_deployments');
        }

        return $this->render(
            'docsDeploymentsDelete.html.twig',
            [
                'deployment' => $jar,
                'docsLiveServer' => getenv('DOCS_LIVE_SERVER'),
            ]
        );
    }

    /**
     * @Route("/admin/docs/deployments/delete/{documentationJarId}", name="admin_docs_deployments_delete_action", requirements={"documentationJarId"="\d+"}, methods={"DELETE"})
     *
     * @param int $documentationJarId
     * @param LoggerInterface $logger
     * @param DocumentationJarRepository $documentationJarRepository
     * @param EntityManagerInterface $entityManager
     * @param DocumentationBuildInformationService $documentationBuildInformationService
     * @param BambooService $bambooService
     * @return Response
     * @throws DocsPackageDoNotCareBranch
     */
    public function delete(
        int $documentationJarId,
        LoggerInterface $logger,
        DocumentationJarRepository $documentationJarRepository,
        EntityManagerInterface $entityManager,
        DocumentationBuildInformationService $documentationBuildInformationService,
        BambooService $bambooService
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_DOCUMENTATION_MAINTAINER');

        $jar = $documentationJarRepository->find($documentationJarId);

        if (null !== $jar && $jar->isDeletable()) {
            $informationFile = $documentationBuildInformationService->generateBuildInformationFromDocumentationJar($jar);
            $documentationBuildInformationService->dumpDeploymentInformationFile($informationFile);
            $bambooBuildTriggered = $bambooService->triggerDocumentationDeletionPlan($informationFile);

            $jar
                ->setBuildKey($bambooBuildTriggered->buildResultKey)
                ->setStatus(DocumentationStatus::STATUS_DELETING);
            $entityManager->persist($jar);
            $entityManager->flush();

            $logger->info(
                'Documentation deleted.',
                [
                    'type' => 'docsRendering',
                    'status' => 'packageDeleted',
                    'triggeredBy' => 'interface',
                    'repository' => $jar->getRepositoryUrl(),
                    'package' => $jar->getPackageName(),
                    'bambooKey' => $bambooBuildTriggered->buildResultKey,
                ]
            );
        }

        return $this->redirectToRoute('admin_docs_deployments');
    }

    /**
     * @Route("/admin/docs/deployments/add", name="admin_docs_deployments_add")
     *
     * @param Request $request
     * @return Response
     */
    public function addConfiguration(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_DOCUMENTATION_MAINTAINER');
        $documentationJar = new DocumentationJar();
        $repositoryUrl = $request->get('documentation_deployment')['repositoryUrl'] ?? '';
        $documentationJar->setRepositoryUrl($repositoryUrl);

        $form = $this->createForm(DocumentationDeployment::class, $documentationJar);
        $form->handleRequest($request);
        if ($repositoryUrl !== '') {
            return $this->forward(self::class . '::addConfigurationStep2');
        }

        return $this->render('docs_deployments/addConfiguration.html.twig', [
            'redirect' => $documentationJar,
            'form' => $form->createView(),
            'showPrev' => false,
        ]);
    }

    /**
     * @Route("/admin/docs/deployments/add/step2", name="admin_docs_deployments_add_step2")
     *
     * @param Request $request
     * @return Response
     */
    public function addConfigurationStep2(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_DOCUMENTATION_MAINTAINER');
        $documentationJar = new DocumentationJar();

        $repositoryUrl = $request->get('documentation_deployment')['repositoryUrl'] ?? '';
        $documentationJar->setRepositoryUrl($repositoryUrl);
        $branch = $request->get('documentation_deployment')['branch'] ?? '';
        $documentationJar->setBranch($branch);

        $form = $this->createForm(DocumentationDeployment::class, $documentationJar, ['step2' => true]);
        $form->setData($documentationJar);
        if ($branch !== '') {
            return $this->forward(self::class . '::addConfigurationStep3');
        }

        return $this->render('docs_deployments/addConfiguration.html.twig', [
            'redirect' => $documentationJar,
            'form' => $form->createView(),
            'showPrev' => true,
        ]);
    }

    /**
     * @Route("/admin/docs/deployments/add/step3", name="admin_docs_deployments_add_step3")
     *
     * @param Request $request
     * @param DocumentationBuildInformationService $documentationBuildInformationService
     * @param BambooService $bambooService
     * @return Response
     * @throws DocsPackageDoNotCareBranch
     */
    public function addConfigurationStep3(Request $request, DocumentationBuildInformationService $documentationBuildInformationService, BambooService $bambooService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_DOCUMENTATION_MAINTAINER');
        $documentationJar = new DocumentationJar();
        $repositoryUrl = $request->get('documentation_deployment')['repositoryUrl'] ?? '';
        $documentationJar->setRepositoryUrl($repositoryUrl);
        $branch = $request->get('documentation_deployment')['branch'] ?? '';
        $documentationJar->setBranch($branch);
        $publicComposerJsonUrl = $request->get('documentation_deployment')['publicComposerJsonUrl'] ?? '';
        $documentationJar->setPublicComposerJsonUrl($publicComposerJsonUrl);

        if ($publicComposerJsonUrl === '') {
            // public composer url not set yet, try to resolve it
            $publicComposerJsonUrl = $this->resolveComposerJsonUrl($repositoryUrl, $branch);
        }
        $documentationJar->setPublicComposerJsonUrl($publicComposerJsonUrl);
        $requestData = $request->request->get('documentation_deployment');
        $requestData['publicComposerJsonUrl'] = $publicComposerJsonUrl;
        $request->request->set('documentation_deployment', $requestData);

        $deploymentInformation = null;
        if ($publicComposerJsonUrl !== '') {
            try {
                $composerJsonObject = $this->resolveComposerJson($documentationBuildInformationService, $publicComposerJsonUrl);
                $documentationBuildInformationService->assertComposerJsonContainsNecessaryData($composerJsonObject);
                $documentationJar
                    ->setPackageName($composerJsonObject->getName())
                    ->setPackageType($composerJsonObject->getType());
                $deploymentInformation = $documentationBuildInformationService
                    ->generateBuildInformationFromDocumentationJar($documentationJar);
            } catch (ComposerJsonNotFoundException | ComposerJsonInvalidException | DocsComposerDependencyException | DocsPackageDoNotCareBranch $e) {
                $this->addFlash('danger', $e->getMessage());
            }
        }

        $form = $this->createForm(DocumentationDeployment::class, $documentationJar, ['step3' => true]);
        $form->handleRequest($request);
        if ($deploymentInformation !== null && $form->isValid()) {
            $documentationJar->setRepositoryUrl($deploymentInformation->repositoryUrl)
                ->setStatus(DocumentationStatus::STATUS_RENDERING)
                ->setBuildKey('')
                ->setPublicComposerJsonUrl($deploymentInformation->publicComposerJsonUrl)
                ->setVendor($deploymentInformation->vendor)
                ->setName($deploymentInformation->name)
                ->setPackageName($deploymentInformation->packageName)
                ->setBranch($deploymentInformation->sourceBranch)
                ->setTargetBranchDirectory($deploymentInformation->targetBranchDirectory)
                ->setTypeLong($deploymentInformation->typeLong)
                ->setTypeShort($deploymentInformation->typeShort);

            $informationFile = $documentationBuildInformationService->generateBuildInformationFromDocumentationJar($documentationJar);
            $documentationBuildInformationService->dumpDeploymentInformationFile($informationFile);
            $bambooBuildTriggered = $bambooService->triggerDocumentationPlan($informationFile);
            $documentationJar->setBuildKey($bambooBuildTriggered->buildResultKey);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($documentationJar);
            $entityManager->flush();
            $this->addFlash('success', 'Configuration added');
            return $this->redirectToRoute('admin_docs_deployments');
        }

        return $this->render('docs_deployments/addConfiguration.html.twig', [
            'redirect' => $documentationJar,
            'form' => $form->createView(),
            'showPrev' => true,
        ]);
    }

    protected function resolveComposerJsonUrl(string $repositoryUrl, string $branch): string
    {
        $repoService = '';
        $parameters = [];
        if (strpos($repositoryUrl, 'github.com') !== false) {
            $repoService = GitRepositoryService::SERVICE_GITHUB;
            $packageParts = explode('/', str_replace(['https://github.com/', '.git'], '', $repositoryUrl));
            $parameters = [
                '{repoName}' => $packageParts[0] . '/' . $packageParts[1],
                '{version}' => $branch,
            ];
        }
        if (strpos($repositoryUrl, 'gitlab.com') !== false) {
            $repoService = GitRepositoryService::SERVICE_GITLAB;
            $parameters = [
                '{baseUrl}' => str_replace('.git', '', $repositoryUrl),
                '{version}' => $branch,
            ];
        }
        if (strpos($repositoryUrl, 'bitbucket.com') !== false) {
            $repoService = GitRepositoryService::SERVICE_BITBUCKET;
            $packageParts = explode('/', str_replace('https://bitbucket.org/', '', $repositoryUrl));
            $parameters = [
                '{baseUrl}' => 'https://bitbucket.org/',
                '{repoName}' => $packageParts[0] . '/' . $packageParts[1],
                '{version}' => $branch,
            ];
        }
        if ($repoService !== '') {
            return (new GitRepositoryService())->resolvePublicComposerJsonUrl($repoService, $parameters);
        }
        return '';
    }

    /**
     * @param DocumentationBuildInformationService $documentationBuildInformationService
     * @param string $publicComposerJsonUrl
     * @return ComposerJson
     * @throws ComposerJsonNotFoundException
     * @throws ComposerJsonInvalidException
     */
    protected function resolveComposerJson(
        DocumentationBuildInformationService $documentationBuildInformationService,
        string $publicComposerJsonUrl
    ): ComposerJson {
        $composerJson = $documentationBuildInformationService->fetchRemoteComposerJson($publicComposerJsonUrl);
        return $documentationBuildInformationService->getComposerJsonObject($composerJson);
    }
}
