<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Command;

use App\Service\RabbitConsumerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A cli worker listening for rabbit mq messages and executes
 * core git split and tag jobs
 *
 * @codeCoverageIgnore
 */
class GitSubtreeSplitWorkerCommand extends Command
{
    protected static $defaultName = 'app:git-core-split-worker';

    private RabbitConsumerService $rabbitService;

    private LoggerInterface $logger;

    /**
     * GitSubtreeSplitWorkerCommand constructor.
     *
     * @param LoggerInterface $logger
     * @param RabbitConsumerService $rabbitService
     */
    public function __construct(LoggerInterface $logger, RabbitConsumerService $rabbitService)
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
     * @throws \ErrorException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logger->info('Starting git core split worker');
        $this->rabbitService->workerLoop();
        // Worker usually never stop, but if they do, they log ... hopefully
        $this->logger->warning('Git core split worker stopped');

        return 0;
    }
}
