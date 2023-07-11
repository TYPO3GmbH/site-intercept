<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Extractor\ForgeNewIssue;
use App\Extractor\GithubPullRequestIssue;
use Redmine\Api\Issue;
use Redmine\Client\Client;

/**
 * Forge service handles all interactions with forge. used by
 * github pull request controller to create a new forge issue
 * for opened pull requests.
 */
class ForgeService
{
    /**
     * @var int TYPO3 core project id on forge
     */
    private int $projectId = 27;

    /**
     * ForgeService constructor.
     */
    public function __construct(private readonly Client $client)
    {
    }

    /**
     * Create a new issue on forge based on GitHub pull request information.
     * Used by GitHub pull request controller.
     */
    public function createIssue(GithubPullRequestIssue $issueDetails): ForgeNewIssue
    {
        $description = $issueDetails->body;
        $description .= "\n\nThis issue was automatically created from " . $issueDetails->url;

        /** @var Issue $issueApi */
        $issueApi = $this->client->getApi('issue');
        $response = $issueApi->create([
            'project_id' => $this->projectId,
            'tracker_id' => 4,
            'subject' => $issueDetails->title,
            'description' => $description,
            'custom_fields' => [
                [
                    'id' => 4,
                    'name' => 'TYPO3 Version',
                    'value' => 12,
                ],
            ],
        ]);

        return new ForgeNewIssue($response);
    }
}
