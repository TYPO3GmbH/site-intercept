<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Extractor;

/**
 * Class represents a push event (web hook).
 *
 * @codeCoverageIgnore
 */
class PushEvent
{
    /**
     * PushEvent constructor.
     */
    public function __construct(protected string $repositoryUrl, protected string $versionString, protected string $urlToComposerFile)
    {
    }

    public function getVersionString(): string
    {
        return $this->versionString;
    }

    public function getRepositoryUrl(): string
    {
        return $this->repositoryUrl;
    }

    public function getUrlToComposerFile(): string
    {
        return $this->urlToComposerFile;
    }
}
