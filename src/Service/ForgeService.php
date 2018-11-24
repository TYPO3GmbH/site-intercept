<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Extractor\ForgeNewIssue;
use App\Extractor\GithubPullRequestIssue;

/**
 * Forge service handles all interactions with forge. used by
 * github pull request controller to create a new forge issue
 * for opened pull requests.
 */
class ForgeService
{
    /**
     * @var string TYPO3 forge base url
     */
    private $url = 'https://forge.typo3.org';

    /**
     * @var int TYPO3 core project id on forge
     */
    private $projectId = 27;

    /**
     * Create a new issue on forge based on github pull request information.
     * Used by github pull request controller.
     *
     * @param GithubPullRequestIssue $issueDetails
     * @return ForgeNewIssue
     */
    public function createIssue(GithubPullRequestIssue $issueDetails): ForgeNewIssue
    {
        $client = new \Redmine\Client($this->url, getenv('FORGE_ACCESS_TOKEN'));
        $description = $issueDetails->body;
        $description .= "\r\n This issue was automatically created from " . $issueDetails->url;
        $response = $client->issue->create(
            [
                'project_id' => $this->projectId,
                'tracker_id' => 4,
                'subject' => $issueDetails->title,
                'description' => $description,
                'custom_fields' => [
                    [
                        'id' => 4,
                        'name' => 'TYPO3 Version',
                        'value' => 8,
                    ]
                ]
            ]
        );
        return new ForgeNewIssue($response);
    }
}
