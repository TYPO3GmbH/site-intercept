<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Command;

use App\Repository\DocumentationJarRepository;
use App\Service\DocumentationService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RenderOtherBranchesFromNewDocsCommand extends Command
{
    protected static $defaultName = 'app:docs-render-new';
    private DocumentationJarRepository $documentationJarRepository;
    private DocumentationService $documentationService;

    public function __construct(DocumentationJarRepository $documentationJarRepository, DocumentationService $documentationService)
    {
        parent::__construct();
        $this->documentationJarRepository = $documentationJarRepository;
        $this->documentationService = $documentationService;
    }

    protected function configure(): void
    {
        $this->setDescription('Command to render missing branches from newly added repositories');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $newRepositories = $this->documentationJarRepository->findBy([
            'new' => true,
            'approved' => true,
        ]);

        if (count($newRepositories) === 0) {
            return 0;
        }
        foreach ($newRepositories as $documentationJar) {
            $this->documentationService->handleNewRepository($documentationJar);
        }

        return 0;
    }
}
