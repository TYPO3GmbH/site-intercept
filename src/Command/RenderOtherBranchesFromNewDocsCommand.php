<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Command;

use App\Entity\DocumentationJar;
use App\Enum\DocumentationStatus;
use App\Exception\Composer\DocsComposerDependencyException;
use App\Exception\Composer\DocsComposerMissingValueException;
use App\Exception\ComposerJsonInvalidException;
use App\Exception\ComposerJsonNotFoundException;
use App\Exception\DocsPackageDoNotCareBranch;
use App\Exception\DocsPackageRegisteredWithDifferentRepositoryException;
use App\Repository\DocumentationJarRepository;
use App\Service\BambooService;
use App\Service\DocumentationBuildInformationService;
use App\Service\GitRepositoryService;
use App\Service\RenderDocumentationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RenderOtherBranchesFromNewDocsCommand extends Command
{
    protected static $defaultName = 'app:docs-render-new';

    /**
     * @var RenderDocumentationService
     */
    protected $renderDocumentationService;

    /**
     * @var DocumentationJarRepository
     */
    protected $documentationJarRepository;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var DocumentationBuildInformationService
     */
    protected $documentationBuildInformationService;

    /**
     * @var BambooService
     */
    protected $bambooService;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        RenderDocumentationService $renderDocumentationService,
        DocumentationJarRepository $documentationJarRepository,
        EntityManagerInterface $entityManager,
        DocumentationBuildInformationService $documentationBuildInformationService,
        BambooService $bambooService,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->renderDocumentationService = $renderDocumentationService;
        $this->documentationJarRepository = $documentationJarRepository;
        $this->entityManager = $entityManager;
        $this->documentationBuildInformationService = $documentationBuildInformationService;
        $this->bambooService = $bambooService;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this->setDescription('Command to render missing branches from newly added repositories');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $newRepositories = $this->documentationJarRepository->findBy([
            'new' => true,
            'approved' => true,
        ]);

        if (count($newRepositories) === 0) {
            return;
        }
        foreach ($newRepositories as $documentationJar) {
            // No need to filter these anymore, this is already done in the service
            $branches = (new GitRepositoryService())->getBranchesFromRepositoryUrl($documentationJar->getRepositoryUrl());

            foreach ($branches as $branchName => $short) {
                // Check if in the mean time someone already rendered this branch
                $alreadyExists = $this->documentationJarRepository->findBy([
                    'repositoryUrl' => $documentationJar->getRepositoryUrl(),
                    'targetBranchDirectory' => $short,
                ]);

                if (count($alreadyExists) > 0) {
                    continue;
                }

                $newJar = (new DocumentationJar())
                    ->setRepositoryUrl($documentationJar->getRepositoryUrl())
                    ->setBranch($branchName);
                try {
                    $publicComposerJsonUrl = $this->resolveComposerJsonUrl($newJar->getRepositoryUrl(), $branchName);
                    $newJar->setPublicComposerJsonUrl($publicComposerJsonUrl);
                    $composerJson = $this->documentationBuildInformationService->fetchRemoteComposerJson($publicComposerJsonUrl);
                    $composerJsonObject = $this->documentationBuildInformationService->getComposerJsonObject($composerJson);
                    $newJar
                        ->setPackageName($composerJsonObject->getName())
                        ->setPackageType($composerJsonObject->getType())
                        ->setExtensionKey($composerJsonObject->getExtensionKey())
                        ->setMinimumTypoVersion($composerJsonObject->getMinimumTypoVersion())
                        ->setMaximumTypoVersion($composerJsonObject->getMaximumTypoVersion());
                    $deploymentInformation = $this->documentationBuildInformationService
                        ->generateBuildInformationFromDocumentationJar($newJar);
                } catch (ComposerJsonNotFoundException | ComposerJsonInvalidException | DocsComposerDependencyException | DocsPackageDoNotCareBranch | DocsComposerMissingValueException | DocsPackageRegisteredWithDifferentRepositoryException $e) {
                    $this->logger->warning(
                        'Can not render documentation: ' . $e->getMessage(),
                        [
                            'type' => 'docsRendering',
                            'status' => 'commandFailed',
                            'triggeredBy' => 'CLI',
                            'exceptionCode' => $e->getCode(),
                            'exceptionMessage' => $e->getMessage(),
                            'repository' => $documentationJar->getRepositoryUrl(),
                            'branch' => $branchName,
                        ]
                    );
                    continue;
                }

                if ($deploymentInformation !== null) {
                    $newJar
                        ->setStatus(DocumentationStatus::STATUS_RENDERING)
                        ->setBuildKey('')
                        ->setVendor($deploymentInformation->vendor)
                        ->setName($deploymentInformation->name)
                        ->setPackageName($deploymentInformation->packageName)
                        ->setExtensionKey($deploymentInformation->extensionKey)
                        ->setTargetBranchDirectory($deploymentInformation->targetBranchDirectory)
                        ->setTypeLong($deploymentInformation->typeLong)
                        ->setTypeShort($deploymentInformation->typeShort)
                        ->setMinimumTypoVersion($deploymentInformation->minimumTypoVersion)
                        ->setMaximumTypoVersion($deploymentInformation->maximumTypoVersion)
                        ->setReRenderNeeded(false)
                        ->setNew(false)
                        ->setApproved(true);

                    $informationFile = $this->documentationBuildInformationService->generateBuildInformationFromDocumentationJar($newJar);
                    $this->documentationBuildInformationService->dumpDeploymentInformationFile($informationFile);
                    $bambooBuildTriggered = $this->bambooService->triggerDocumentationPlan($informationFile);
                    $newJar->setBuildKey($bambooBuildTriggered->buildResultKey);
                    $this->entityManager->persist($newJar);

                    // Flushing in ForEach is needed in case Bamboo finishes the build before this command
                    // has finished running through all the branches
                    $this->entityManager->flush();
                }
            }

            $documentationJar->setNew(false);
            $this->entityManager->persist($documentationJar);
            $this->entityManager->flush();
        }
    }

    protected function resolveComposerJsonUrl(string $repositoryUrl, string $branch): string
    {
        $repoService = '';
        $parameters = [];
        if (strpos($repositoryUrl, 'https://github.com') !== false) {
            $repoService = GitRepositoryService::SERVICE_GITHUB;
            $packageParts = explode('/', str_replace(['https://github.com/', '.git'], '', $repositoryUrl));
            $parameters = [
                '{repoName}' => $packageParts[0] . '/' . $packageParts[1],
                '{version}' => $branch,
            ];
        }
        if (strpos($repositoryUrl, 'https://gitlab.com') !== false) {
            $repoService = GitRepositoryService::SERVICE_GITLAB;
            $parameters = [
                '{baseUrl}' => str_replace('.git', '', $repositoryUrl),
                '{version}' => $branch,
            ];
        }
        if (strpos($repositoryUrl, 'https://bitbucket.org') !== false) {
            $repoService = GitRepositoryService::SERVICE_BITBUCKET_CLOUD;
            $packageParts = explode('/', str_replace('https://bitbucket.org/', '', $repositoryUrl));
            $parameters = [
                '{baseUrl}' => 'https://bitbucket.org',
                '{repoName}' => $packageParts[0] . '/' . str_replace('.git', '', $packageParts[1]),
                '{version}' => $branch,
            ];
        }
        // Last if statement. If this is reached, it MUST be a bitbucket server repository
        if ($repoService === '') {
            $repoService = GitRepositoryService::SERVICE_BITBUCKET_SERVER;
            $repositoryUrl = str_replace('.git', '', $repositoryUrl);
            $packageParts = explode('/', $repositoryUrl);
            $package = array_pop($packageParts);
            $project = array_pop($packageParts);
            $tag = false;
            if (preg_match('/^v?(\d+.\d+.\d+)$/', $branch)) {
                $tag = true;
            }
            $parameters = [
                '{baseUrl}' => 'https://' . explode('/', str_replace('https://', '', $repositoryUrl))[0],
                '{package}' => $package,
                '{project}' => $project,
                '{version}' => $branch,
                '{type}' => $tag ? 'tags' : 'heads',
            ];
        }
        if ($repoService !== '') {
            return (new GitRepositoryService())->resolvePublicComposerJsonUrl($repoService, $parameters);
        }
        return '';
    }
}
