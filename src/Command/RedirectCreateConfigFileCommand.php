<?php

namespace App\Command;

use App\Service\NginxService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RedirectCreateConfigFileCommand extends Command
{
    protected static $defaultName = 'redirect:create-config-and-reload';

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
        if ($this->nginxService->createNewConfigAndReload()) {
            $io->success('nginx redirect configuration created.');
        } else {
            $io->error('Oops, something went wrong...');
        }
    }
}
