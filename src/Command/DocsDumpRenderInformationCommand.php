<?php

declare(strict_types=1);

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
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @codeCoverageIgnore No business logic code, no production app code, just glue
 */
#[AsCommand(name: 'app:docs-dump-render-info', description: 'Command to re-render all docs or one specific')]
class DocsDumpRenderInformationCommand extends Command
{
    public function __construct(
        private readonly RenderDocumentationService $renderDocumentationService,
        private readonly DocumentationJarRepository $documentationJarRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('all', 'a', InputOption::VALUE_NONE, 're-dump all existing configurations')
            ->addOption('configuration', 'c', InputOption::VALUE_OPTIONAL, 'dump configuration by given ID')
            ->addOption('package', 'p', InputOption::VALUE_OPTIONAL, 'dump configuration by given package and target directory, e.g. typo3/team-t3docteam:main')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $all = $input->getOption('all') ?? false;
        $id = $input->getOption('configuration');
        $package = $input->getOption('package');

        $io->title('Render Documentation');

        if (false === $all && null === $id && null === $package) {
            $io->error('At least one option is required: --all, --configuration or --package');

            return Command::FAILURE;
        }

        try {
            if ($all) {
                if ($io->confirm('Are you really sure, you want to dump info about ALL docs?', false)) {
                    foreach ($this->documentationJarRepository->findAll() as $documentationJar) {
                        try {
                            $this->renderDocumentation($documentationJar);
                            $io->success('Dumped info for package "' . $documentationJar->getPackageName() . '" with target branch "' . $documentationJar->getTargetBranchDirectory() . '"');
                            // avoid stopping the whole queue because of a broken / irrelevant package
                        } catch (DuplicateDocumentationRepositoryException|DocsPackageDoNotCareBranch $exception) {
                            $io->error($exception->getMessage());
                        }
                    }
                    $io->success('Dumped all docs.');
                } else {
                    $io->success('Dumping of ALL documentations aborted');
                }
            } elseif (null !== $id) {
                $this->renderConfiguration((int) $id);
                $io->success('Info dumped.');
            } elseif (null !== $package) {
                $this->renderPackage($package);
                $io->success('Info dumped');
            } else {
                $io->error('Something went wrong, please check your input options');
            }
        } catch (DocsPackageDoNotCareBranch $exception) {
            $io->writeln($exception->getMessage());
        }

        return Command::SUCCESS;
    }

    /**
     * @throws DocsPackageDoNotCareBranch
     * @throws DuplicateDocumentationRepositoryException
     */
    protected function renderConfiguration(int $id): void
    {
        $documentationJar = $this->documentationJarRepository->find($id);
        if (null === $documentationJar) {
            throw new \InvalidArgumentException('no valid id for a documentationJar given', 1558609697);
        }
        $this->renderDocumentation($documentationJar);
    }

    /**
     * @throws DocsPackageDoNotCareBranch
     * @throws DuplicateDocumentationRepositoryException
     */
    protected function renderPackage(string $package): void
    {
        [$packageName, $version] = explode(':', $package);
        if (empty($packageName) || empty($version)) {
            throw new \InvalidArgumentException('no valid package identifier given: ' . $package, 1558609831);
        }
        $documentationJar = $this->documentationJarRepository->findByPackageIdentifier($package);
        if (null === $documentationJar) {
            throw new \InvalidArgumentException('no valid documentationJar could be resolved for packageIdentifier:' . $package, 1558610587);
        }
        $this->renderDocumentation($documentationJar);
    }

    /**
     * @throws DocsPackageDoNotCareBranch
     * @throws DuplicateDocumentationRepositoryException
     */
    protected function renderDocumentation(DocumentationJar $documentationJar): void
    {
        /* @noinspection UnusedFunctionResultInspection */
        $this->renderDocumentationService->dumpRenderingInformationByDocumentationJar($documentationJar);
    }
}
