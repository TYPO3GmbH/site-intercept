<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Extractor;

/**
 * Class represents a push event (web hook)
 * @codeCoverageIgnore
 */
class PushEvent
{
    /**
     * @var string A tag or a branch name
     */
    protected string $versionString;

    /**
     * @var string Repository url to clone
     */
    protected string $repositoryUrl;

    protected string $fileUrlFormat;

    /**
     * PushEvent constructor.
     * @param string $repositoryUrl
     * @param string $versionString
     * @param string $fileUrlFormat
     */
    public function __construct(string $repositoryUrl, string $versionString, string $fileUrlFormat)
    {
        $this->repositoryUrl = $repositoryUrl;
        $this->versionString = $versionString;
        $this->fileUrlFormat = $fileUrlFormat;
    }

    /**
     * @return string
     */
    public function getVersionString(): string
    {
        return $this->versionString;
    }

    /**
     * @return string
     */
    public function getRepositoryUrl(): string
    {
        return $this->repositoryUrl;
    }

    /**
     * @return string
     */
    public function getUrlToComposerFile(): string
    {
        return $this->getUrlToFile('composer.json');
    }

    /**
     * @return string
     */
    public function getUrlToFile(string $file): string
    {
        return str_replace('{file}', $file, $this->fileUrlFormat);
    }
}
