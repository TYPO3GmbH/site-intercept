<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Command;

use App\Service\RabbitSplitService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GitSubtreeSplitWorkerCommand extends Command
{
    /**
     * @var RabbitSplitService
     */
    private $rabbitService;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * GitSubtreeSplitWorkerCommand constructor.
     *
     * @param LoggerInterface $logger
     * @param RabbitSplitService $rabbitService
     */
    public function __construct(LoggerInterface $logger, RabbitSplitService $rabbitService)
    {
        parent::__construct();
        $this->logger = $logger;
        $this->rabbitService = $rabbitService;
    }

    /**
     * Standard command information
     */
    protected function configure()
    {
        $this
            // Name of the command (the part after "bin/console")
            ->setName('app:git-core-split-worker')
            // Short description shown while running "php bin/console list"
            ->setDescription('Run a worker for the git core split jobs')
            // Full command description shown when running the command with the "--help" option
            ->setHelp('')
        ;
    }

    /**
     * Start the rabbit mq listener.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger->info('Starting git core split worker');
        $this->rabbitService->workerLoop();
        // Worker usually never stop, but if they do, they log ... hopefully
        $this->logger->warning('Git core split worker stopped');
    }

}