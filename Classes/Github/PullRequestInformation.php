<?php
declare(strict_types = 1);

namespace T3G\Intercept\Github;

class PullRequestInformation
{


    public function transform(string $requestPayload) : array
    {
        $fullPullRequestInformation = json_decode($requestPayload, true);
        $pullRequestInformation = [
            'diffUrl' => $fullPullRequestInformation['pull_request']['diff_url'],
            'userUrl' => $fullPullRequestInformation['pull_request']['user']['url'],
            'issueUrl' => $fullPullRequestInformation['pull_request']['issue_url']
        ];

        return $pullRequestInformation;
    }
}