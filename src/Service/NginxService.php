<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Repository\RedirectRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class NginxService
{
    /**
     * @var RedirectRepository
     */
    protected $redirectRepository;

    /**
     * @var ParameterBagInterface
     */
    protected $parameterBag;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    protected $redirectTemplate = '# Rule: %d | Created: %s | Updated: %s
location = %s {
    return %d https://$host%s;
}';

    /**
     * NginxService constructor.
     * @param RedirectRepository $redirectRepository
     * @param ParameterBagInterface $parameterBag
     * @param Filesystem $filesystem
     * @param LoggerInterface $logger
     */
    public function __construct(RedirectRepository $redirectRepository, ParameterBagInterface $parameterBag, Filesystem $filesystem, LoggerInterface $logger)
    {
        $this->redirectRepository = $redirectRepository;
        $this->parameterBag = $parameterBag;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
    }

    protected function createBackupOfRedirectConfigFile(): void
    {
        $filename = $this->parameterBag->get('kernel.project_dir') . '/var/cache/nginx/nginx_redirects.conf';
        if ($this->filesystem->exists($filename)) {
            $this->filesystem->copy($filename, $filename . '.bak');
        }
    }

    protected function restoreBackupOfRedirectConfigFile(): void
    {
        $filename = $this->parameterBag->get('kernel.project_dir') . '/var/cache/nginx/nginx_redirects.conf';
        if ($this->filesystem->exists($filename . '.bak')) {
            $this->filesystem->copy($filename . '.bak', $filename);
        }
    }

    protected function createRedirectConfigFile(): void
    {
        $redirects = $this->redirectRepository->findAll();
        $content = '';
        foreach ($redirects as $redirect) {
            $content .= chr(10) . sprintf($this->redirectTemplate,
                $redirect->getId(),
                $redirect->getCreatedAt()->format('d.m.Y H:i'),
                $redirect->getUpdatedAt()->format('d.m.Y H:i'),
                $redirect->getSource(),
                $redirect->getStatusCode(),
                $redirect->getTarget()
            );
        }
        $filename = $this->parameterBag->get('kernel.project_dir') . '/var/cache/nginx/nginx_redirects.conf';
        $this->filesystem->dumpFile($filename, $content);
    }

    protected function checkNginxConfiguration(): bool
    {
        $process = new Process(['nginx', '-t']);
        $process->run();
        $errors = $process->getErrorOutput();
        if ($errors !== '') {
            $this->logger->error('nginx config check: ' . $errors);
        }
        return $process->isSuccessful();
    }

    protected function reloadNginx(): bool
    {
        $process = new Process(['service', 'nginx', 'restart']);
        $process->run();
        $errors = $process->getErrorOutput();
        if ($errors !== '') {
            $this->logger->error('nginx reload: ' . $errors);
        }
        return $process->isSuccessful();
    }

    public function createNewConfigAndReload(): bool
    {
        try {
            $this->createBackupOfRedirectConfigFile();
            $this->createRedirectConfigFile();
            $syntaxCheck = $this->checkNginxConfiguration();
            if (!$syntaxCheck) {
                $this->logger->error('syntax check failed');
                $this->restoreBackupOfRedirectConfigFile();
                return false;
            }
            $webServerReload = $this->reloadNginx();
            if (!$webServerReload) {
                $this->logger->error('webserver reload failed');
                $this->restoreBackupOfRedirectConfigFile();
                $this->reloadNginx();
                return false;
            }
            return true;
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage(), [$exception]);
            $this->restoreBackupOfRedirectConfigFile();
            $this->reloadNginx();
            return false;
        }
    }
}
