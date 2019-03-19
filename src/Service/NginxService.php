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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class NginxService
{
    /**
     * @var RedirectRepository
     */
    protected $redirectRepository;

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var BambooService
     */
    protected $bambooService;

    protected $redirectTemplate = '# Rule: %d | Created: %s | Updated: %s
location = %s {
    return %d https://$host%s;
}';

    /**
     * NginxService constructor.
     * @param RedirectRepository $redirectRepository
     * @param KernelInterface $kernel
     * @param Filesystem $filesystem
     * @param LoggerInterface $logger
     * @param BambooService $bambooService
     */
    public function __construct(RedirectRepository $redirectRepository, KernelInterface $kernel, Filesystem $filesystem, LoggerInterface $logger, BambooService $bambooService)
    {
        $this->redirectRepository = $redirectRepository;
        $this->kernel = $kernel;
        $this->filesystem = $filesystem;
        $this->logger = $logger;
        $this->bambooService = $bambooService;
    }

    public function createRedirectConfigFile(): string
    {
        $redirects = $this->redirectRepository->findAll();
        $content = '';
        foreach ($redirects as $redirect) {
            $content .= chr(10) . sprintf(
                $this->redirectTemplate,
                $redirect->getId(),
                $redirect->getCreatedAt()->format('d.m.Y H:i'),
                $redirect->getUpdatedAt()->format('d.m.Y H:i'),
                $redirect->getSource(),
                $redirect->getStatusCode(),
                $redirect->getTarget()
            );
        }

        $filename = $this->kernel->getCacheDir() . '/nginx/nginx_redirects_%s.conf';
        $filename = sprintf($filename, (new \DateTime())->format('Ymd-His'));
        $this->filesystem->dumpFile($filename, $content);
        return $filename;
    }

    public function getFileContent(string $filename): string
    {
        $filename = $this->kernel->getCacheDir() . '/nginx/' . $filename;
        if ($this->filesystem->exists($filename)) {
            return file_get_contents($filename);
        }
        return '';
    }

    public function createDeploymentJob(string $filename): void
    {
        $this->bambooService->triggerDocumentationRedirectsPlan(basename($filename));
    }
}
