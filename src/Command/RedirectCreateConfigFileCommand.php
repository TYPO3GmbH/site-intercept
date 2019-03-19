<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Command;

use App\Service\NginxService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RedirectCreateConfigFileCommand extends Command
{
    protected static $defaultName = 'redirect:create-config-and-deploy';

    /**
     * @var NginxService
     */
    protected $nginxService;

    public function __construct(?string $name = null, NginxService $nginxService)
    {
        parent::__construct($name);
        $this->nginxService = $nginxService;
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
        $this->nginxService->createDeploymentJob($filename);
        $io->success('done');
    }
}
