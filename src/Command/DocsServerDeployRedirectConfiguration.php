<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Command;

use App\Service\DocsServerNginxService;
use App\Service\GithubService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Use this command to create the nginx redirects configuration file and trigger a deployment.
 * @codeCoverageIgnore
 */
class DocsServerDeployRedirectConfiguration extends Command
{
    protected static $defaultName = 'app:docs-redirect-deploy';

    protected DocsServerNginxService $nginxService;

    protected GithubService $githubService;

    public function __construct(string $name = 'app:docs-redirect-deploy', DocsServerNginxService $nginxService, GithubService $githubService)
    {
        parent::__construct($name);
        $this->nginxService = $nginxService;
        $this->githubService = $githubService;
    }

    /**
     * @return void
     */
    protected function configure()
    {
        $this
            ->setDescription('Deploy current docs redirects to live server')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Deploy nginx redirects configuration');
        $this->githubService->triggerDocumentationRedirectsPlan();
        $io->success('done');

        return 0;
    }
}
