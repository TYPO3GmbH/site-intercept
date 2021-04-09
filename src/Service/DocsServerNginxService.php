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
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * This class creates a nginx configuration file which contains all redirects from database.
 * It contains also a helper method to get the content of this file.
 *
 */
class DocsServerNginxService
{
    protected DocsServerRedirectRepository $redirectRepository;

    protected string $privateDir;

    protected string $staticDir;

    protected Filesystem $filesystem;

    protected string $redirectTemplate = '# Rule: %d | Created: %s | Updated: %s
location = %s {
    return %d %s;
}';

    protected string $legacyRedirectTemplate = '# Rule: %d | Created: %s | Updated: %s | Legacy
location ~ ^%s(.*) {
    return %d %s$1;
}';

    /**
     * NginxService constructor.
     * @param DocsServerRedirectRepository $redirectRepository
     * @param Filesystem $fileSystem
     * @param string $privateDir
     * @param string $staticDir
     */
    public function __construct(DocsServerRedirectRepository $redirectRepository, Filesystem $fileSystem, string $privateDir, string $staticDir)
    {
        $this->redirectRepository = $redirectRepository;
        $this->privateDir = $privateDir;
        $this->filesystem = $fileSystem;
        $this->staticDir = $staticDir;
    }

    public function getDynamicConfiguration(): array
    {
        $redirects = $this->redirectRepository->findAll();
        $content = [];
        foreach ($redirects as $redirect) {
            $content[] = sprintf(
                $redirect->getIsLegacy() ? $this->legacyRedirectTemplate : $this->redirectTemplate,
                $redirect->getId(),
                $redirect->getCreatedAt()->format('d.m.Y H:i'),
                $redirect->getUpdatedAt()->format('d.m.Y H:i'),
                $redirect->getSource(),
                $redirect->getStatusCode(),
                $redirect->getTarget()
            );
        }

        return $content;
    }

    /**
     * Finds the static configuration file being in place
     *
     * @return SplFileInfo|null
     */
    public function getStaticConfiguration(): ?SplFileInfo
    {
        if (!is_dir($this->getStaticDirectory())) {
            return null;
        }

        $file = (new Finder())
            ->in($this->getStaticDirectory())
            ->files()
            ->name('redirects.conf');

        $iterator = $file->getIterator();
        $iterator->rewind();
        return $iterator->current();
    }

    /**
     * @return string
     */
    private function getStaticDirectory(): string
    {
        return rtrim($this->staticDir, '/') . '/';
    }
}
