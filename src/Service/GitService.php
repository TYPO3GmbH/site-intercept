<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use Gitonomy\Git\Admin;
use Gitonomy\Git\Repository;
use Psr\Log\LoggerInterface;

readonly class GitService
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function getRepository(string $checkoutDirectory): Repository
    {
        return new Repository($checkoutDirectory, [
            'logger' => $this->logger,
        ]);
    }

    public function cloneAndCheckout(Repository $repository, string $repositoryUrl, string $branch): Repository
    {
        $checkoutDirectory = $repository->getWorkingDir();
        $this->logger->info('Cloning {repository} to {checkout}, checking out {branch}', [
            'repository' => $repositoryUrl,
            'checkout' => $checkoutDirectory,
            'branch' => $branch,
        ]);

        if (!is_dir($checkoutDirectory) && !mkdir($checkoutDirectory, recursive: true) && !is_dir($checkoutDirectory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $checkoutDirectory));
        }

        if (!is_dir($checkoutDirectory . '/.git')) {
            $repository = Admin::cloneTo($checkoutDirectory, $repositoryUrl, false);
            $repository->run('config', ['--add', 'checkout.defaultRemote', 'origin']);
            $repository->run('config', ['pull.rebase', 'true']);
            $repository->run('checkout', [$branch]);
        } else {
            $repository->run('fetch');
            $repository->run('checkout', [$branch]);
            $repository->run('pull');
        }

        return $repository;
    }
}
