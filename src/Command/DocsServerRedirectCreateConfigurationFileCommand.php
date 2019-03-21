<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Command;

use App\Service\BambooService;
use App\Service\DocsServerNginxService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Use this command to create the nginx redirects configuration file and trigger a deployment.
 */
class DocsServerRedirectCreateConfigurationFileCommand extends Command
{
    protected static $defaultName = 'redirect:create-config-and-deploy';

    /**
     * @var DocsServerNginxService
     */
    protected $nginxService;

    /**
     * @var BambooService
     */
    protected $bambooService;

    public function __construct(?string $name = null, DocsServerNginxService $nginxService, BambooService $bambooService)
    {
        parent::__construct($name);
        $this->nginxService = $nginxService;
        $this->bambooService = $bambooService;
    }

    protected function configure()
    {
        $this
            ->setDescription('Create nginx redirect configuration file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Creating nginx redirect configuration file');
        $filename = $this->nginxService->createRedirectConfigFile();
        $io->writeln('nginx redirect configuration created: ' . $filename);
        $io->writeln('trigger now the deployment');
        $this->bambooService->triggerDocumentationRedirectsPlan(basename($filename));
        $io->success('done');
    }
}
