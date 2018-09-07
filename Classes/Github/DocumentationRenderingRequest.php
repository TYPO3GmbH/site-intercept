<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/build-information-service.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Intercept\Github;

class DocumentationRenderingRequest extends PushEvent
{
    /**
     * @var string
     */
    protected $versionNumber = '';
    /**
     * @var string
     */
    protected $repositoryUrl = '';

    public function __construct(string $requestPayload)
    {
        parent::__construct($requestPayload);

        $fullPullRequestInformation = json_decode($requestPayload, true);

        $this->versionNumber = $this->getVersionNumberFromRef($fullPullRequestInformation['ref']);
        $this->repositoryUrl = $fullPullRequestInformation['repository']['clone_url'];
    }

    public function getVersionNumber(): string
    {
        return $this->versionNumber;
    }

    public function getRepositoryUrl(): string
    {
        return $this->repositoryUrl;
    }

    protected function getVersionNumberFromRef(string $ref): string
    {
        if (strpos($ref, 'refs/tags/') === 0) {
            return str_replace('refs/tags/', '', $ref);
        }

        return $this->getBranchName();
    }
}
