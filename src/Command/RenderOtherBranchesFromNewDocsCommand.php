<?php

declare(strict_types=1);

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
    protected static $defaultDescription = 'Command to render missing branches from newly added repositories';

    public function __construct(
        private readonly DocumentationJarRepository $documentationJarRepository,
        private readonly DocumentationService $documentationService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $newRepositories = $this->documentationJarRepository->findBy([
            'new' => true,
            'approved' => true,
        ]);

        if (0 === count($newRepositories)) {
            return Command::SUCCESS;
        }
        foreach ($newRepositories as $documentationJar) {
            $this->documentationService->handleNewRepository($documentationJar);
        }

        return Command::SUCCESS;
    }
}
