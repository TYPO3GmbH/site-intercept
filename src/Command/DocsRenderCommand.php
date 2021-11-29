<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Command;

use App\Entity\DocumentationJar;
use App\Exception\DocsPackageDoNotCareBranch;
use App\Exception\DuplicateDocumentationRepositoryException;
use App\Repository\DocumentationJarRepository;
use App\Service\RenderDocumentationService;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @codeCoverageIgnore No business logic code, no production app code, just glue
 */
class DocsRenderCommand extends Command
{
    protected static $defaultName = 'app:docs-render';

    protected RenderDocumentationService $renderDocumentationService;

    protected DocumentationJarRepository $documentationJarRepository;

    public function __construct(RenderDocumentationService $renderDocumentationService, DocumentationJarRepository $documentationJarRepository)
    {
        parent::__construct();
        $this->renderDocumentationService = $renderDocumentationService;
        $this->documentationJarRepository = $documentationJarRepository;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Command to re-render all docs or one specific')
            ->addOption('configuration', 'c', InputOption::VALUE_OPTIONAL, 'render configuration by given ID')
            ->addOption('package', 'p', InputOption::VALUE_OPTIONAL, 'render configuration by given package and target directory, e.g. typo3/team-t3docteam:main')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $id = $input->getOption('configuration');
        $package = $input->getOption('package');

        $io->title('Render Documentation');

        if ($id === null && $package === null) {
            $io->error('At least one option is required: --all, --configuration or --package');
            return 1;
        }

        try {
            if ($id !== null) {
                $this->renderConfiguration((int)$id);
                $io->success('Rendering started. This will require some time.');
            } elseif ($package !== null) {
                $this->renderPackage($package);
                $io->success('Rendering started. This will require some time.');
            } else {
                $io->error('Something went wrong, please check your input options');
            }
        } catch (DocsPackageDoNotCareBranch $exception) {
            $io->writeln($exception->getMessage());
        }

        return 0;
    }

    /**
     * @param int $id
     * @throws DocsPackageDoNotCareBranch
     * @throws DuplicateDocumentationRepositoryException
     */
    protected function renderConfiguration(int $id): void
    {
        $documentationJar = $this->documentationJarRepository->find($id);
        if ($documentationJar === null) {
            throw new InvalidArgumentException('no valid id for a documentationJar given', 1558609697);
        }
        $this->renderDocumentation($documentationJar);
    }

    /**
     * @param string $package
     * @throws DocsPackageDoNotCareBranch
     * @throws DuplicateDocumentationRepositoryException
     */
    protected function renderPackage(string $package): void
    {
        [$packageName, $version] = explode(':', $package);
        if (empty($packageName) || empty($version)) {
            throw new InvalidArgumentException('no valid package identifier given', 1558609831);
        }
        $documentationJar = $this->documentationJarRepository->findByPackageIdentifier($package);
        if ($documentationJar === null) {
            throw new InvalidArgumentException('no valid documentationJar could be resolved for packageIdentifier', 1558610587);
        }
        $this->renderDocumentation($documentationJar);
    }

    /**
     * @param DocumentationJar $documentationJar
     * @throws DocsPackageDoNotCareBranch
     * @throws DuplicateDocumentationRepositoryException
     */
    protected function renderDocumentation(DocumentationJar $documentationJar): void
    {
        /** @noinspection UnusedFunctionResultInspection */
        $this->renderDocumentationService->renderDocumentationByDocumentationJar($documentationJar, 'CLI');
    }
}
