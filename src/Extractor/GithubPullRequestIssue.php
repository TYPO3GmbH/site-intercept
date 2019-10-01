<?php
declare(strict_types = 1);

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
 * Extract information from a github pull request issue.
 * Triggered by github api service to retrieve at
 * least title and url from pull request details.
 */
class GithubPullRequestIssue
{
    /**
     * @var string Github new pull request title, eg. 'Improve foo'
     */
    public $title;

    /**
     * @var string (optional) Github new pull request body, eg. 'Fixes whatever'
     */
    public $body;

    /**
     * @var string Github new pull request url, eg. 'https://github.com/psychomieze/TYPO3.CMS/pull/1`
     */
    public $url;

    /**
     * Extract information from a github pull request issue.
     *
     * @param ResponseInterface $response Response of a github issue API get
     * @throws DoNotCareException
     */
    public function __construct(ResponseInterface $response)
    {
        $responseBody = (string)$response->getBody();
        $issueInformation = json_decode($responseBody, true);
        $this->title = (string)$issueInformation['title'] ?? '';
        $this->body = (string)$issueInformation['body'] ?? '';
        $this->url = (string)$issueInformation['html_url'] ?? '';

        // Do not care if issue information is not complete for whatever reason
        if (empty($this->title) || empty($this->url)) {
            throw new DoNotCareException();
        }
    }
}
