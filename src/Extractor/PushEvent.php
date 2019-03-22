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
    protected $versionString;

    /**
     * @var string Repository url to clone
     */
    protected $repositoryUrl;

    /**
     * @var string
     */
    protected $urlToComposerFile;

    public function __construct(string $repositoryUrl, string $versionString, string $urlToComposerFile)
    {
        $this->repositoryUrl = $repositoryUrl;
        $this->versionString = $versionString;
        $this->urlToComposerFile = $urlToComposerFile;
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
        return $this->urlToComposerFile;
    }
}
