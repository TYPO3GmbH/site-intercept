<?php
declare(strict_types = 1);

namespace T3G\Intercept;

use T3G\Intercept\Github\IssueInformation;
use T3G\Intercept\Github\PullRequestInformation;
use T3G\Intercept\Github\UserInformation;
use T3G\Intercept\Github\Client;

class GithubToGerritController
{

    protected $githubRequests;

    public function __construct()
    {
        $this->githubRequests = new Client();
    }

    public function transformPullRequestToGerritReview(string $payload)
    {
        $pullRequestInformation = new PullRequestInformation();
        $pullRequestUrls = $pullRequestInformation->transform($payload);

        $issueData = $this->getIssueData($pullRequestUrls['issueUrl']);

        $userData = $this->getUserData($pullRequestUrls['userUrl']);

    }


    protected function getIssueData(string $issueUrl) : array
    {
        $issueResponse = $this->githubRequests->get($issueUrl);
        $issueInformation = new IssueInformation();
        return $issueInformation->transform($issueResponse);
    }

    protected function getUserData(string $userUrl)
    {
        $userResponse = $this->githubRequests->get($userUrl);
        $userInformation = new UserInformation();
        return $userInformation->transform($userResponse);
    }
}