<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Repository\DocsServerRedirectRepository;
use Symfony\Component\Filesystem\Filesystem;

/**
 * This class creates a nginx configuration file which contains all redirects from database.
 * It contains also a helper method to get the content of this file.
 *
 */
class DocsServerNginxService
{
    /**
     * @var DocsServerRedirectRepository
     */
    protected $redirectRepository;

    /**
     * @var string
     */
    protected $privateDir;

    /**
     * @var string
     */
    protected $subDir;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    protected $redirectTemplate = '# Rule: %d | Created: %s | Updated: %s
location = %s {
    return %d https://$host%s;
}';

    /**
     * NginxService constructor.
     * @param DocsServerRedirectRepository $redirectRepository
     * @param Filesystem $fileSystem
     * @param string $privateDir
     * @param string $subDir
     */
    public function __construct(DocsServerRedirectRepository $redirectRepository, Filesystem $fileSystem, string $privateDir, string $subDir)
    {
        $this->redirectRepository = $redirectRepository;
        $this->privateDir = $privateDir;
        $this->subDir = $subDir;
        $this->filesystem = $fileSystem;
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

        $filename = $this->getPrivateDirectory() . 'nginx_redirects_%s.conf';
        $filename = sprintf($filename, (new \DateTime())->format('Ymd-His'));
        $this->filesystem->dumpFile($filename, $content);
        return $filename;
    }

    public function getFileContent(string $filename): string
    {
        $filename = $this->getPrivateDirectory() . $filename;
        if ($this->filesystem->exists($filename)) {
            return file_get_contents($filename);
        }
        return '';
    }

    /**
     * @return string
     */
    private function getPrivateDirectory(): string
    {
        return rtrim($this->privateDir, '/') . '/' . rtrim($this->subDir, '/') . '/';
    }
}
