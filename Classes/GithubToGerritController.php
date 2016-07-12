<?php
declare(strict_types = 1);

namespace T3G\Intercept;

use T3G\Intercept\Forge\Client as ForgeClient;
use T3G\Intercept\Gerrit\CommitMessageCreator;
use T3G\Intercept\Git\Client;
use T3G\Intercept\Github\IssueInformation;
use T3G\Intercept\Github\PatchSaver;
use T3G\Intercept\Github\PullRequestInformation;
use T3G\Intercept\Github\UserInformation;
use T3G\Intercept\Github\Client as GithubClient;

class GithubToGerritController
{

    protected $githubClient;
    protected $forgeClient;

    public function __construct()
    {
        $this->githubClient = new GithubClient();
        $this->forgeClient = new ForgeClient();
    }

    public function transformPullRequestToGerritReview(string $payload)
    {
        $pullRequestInformation = new PullRequestInformation();
        $pullRequestUrls = $pullRequestInformation->transform($payload);

        $issueData = $this->getIssueData($pullRequestUrls['issueUrl']);
        $userData = $this->getUserData($pullRequestUrls['userUrl']);

        $result = $this->forgeClient->createIssue($issueData['title'], $issueData['body']);
        $issueNumber = (int)$result->id;

        $commitMessageCreator = new CommitMessageCreator();
        $commitMessage = $commitMessageCreator->create($issueData['title'], $issueData['body'], $issueNumber);

        $patchSaver = new PatchSaver();
        $localDiff = $patchSaver->getLocalDiff($pullRequestUrls['diffUrl']);

        $gitClient = new Client();
        $gitClient->commitPatchAsUser($localDiff, $userData, $commitMessage);

    }


    protected function getIssueData(string $issueUrl) : array
    {
        $issueResponse = $this->githubClient->get($issueUrl);
        $issueInformation = new IssueInformation();
        return $issueInformation->transform($issueResponse);
    }

    protected function getUserData(string $userUrl)
    {
        $userResponse = $this->githubClient->get($userUrl);
        $userInformation = new UserInformation();
        return $userInformation->transform($userResponse);
    }
}