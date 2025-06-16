<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Command;

use App\Service\GithubService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Use this command to create the nginx redirects configuration file and trigger a deployment.
 *
 * @codeCoverageIgnore
 */
#[AsCommand(name: 'app:docs-redirect-deploy', description: 'Deploy current docs redirects to live server')]
class DocsServerDeployRedirectConfiguration extends Command
{
    public function __construct(
        private readonly GithubService $githubService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Deploy nginx redirects configuration');
        $this->githubService->triggerDocumentationRedirectsPlan();
        $io->success('done');

        return Command::SUCCESS;
    }
}
