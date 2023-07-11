<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Extractor;

use App\Exception\DoNotCareException;
use Psr\Http\Message\ResponseInterface;

/**
 * Extract information from a GitHub pull request issue.
 * Triggered by GitHub api service to retrieve at
 * least title and url from pull request details.
 */
readonly class GithubPullRequestIssue
{
    /**
     * @var string GitHub new pull request title, e.g. 'Improve foo'
     */
    public string $title;

    /**
     * @var string (optional) GitHub new pull request body, e.g. 'Fixes whatever'
     */
    public string $body;

    /**
     * @var string GitHub new pull request url, e.g. 'https://github.com/psychomieze/TYPO3.CMS/pull/1`
     */
    public string $url;

    /**
     * Extract information from a GitHub pull request issue.
     *
     * @param ResponseInterface $response Response of a GitHub issue API get
     *
     * @throws DoNotCareException
     */
    public function __construct(ResponseInterface $response)
    {
        $responseBody = (string) $response->getBody();
        $issueInformation = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
        $this->title = (string) ($issueInformation['title'] ?? '');
        $this->body = (string) ($issueInformation['body'] ?? '');
        $this->url = (string) ($issueInformation['html_url'] ?? '');

        if (empty($this->title) || empty($this->url)) {
            throw new DoNotCareException('Do not care if issue information is not complete for whatever reason');
        }
    }
}
