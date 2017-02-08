<?php
declare(strict_types = 1);

namespace T3G\Intercept\Github;

use T3G\Intercept\Exception\DoNotCareException;

/**
 * Class PullRequestInformation
 *
 * @package T3G\Intercept\Github
 */
class PullRequest
{
    const CONTRIB_GUIDE = 'https://docs.typo3.org/typo3cms/ContributionWorkflowGuide/';
    public $diffUrl;
    public $userUrl;
    public $issueUrl;
    public $pullRequestUrl;
    public $commentsUrl;
    public $branch;

    /**
     * @var \T3G\Intercept\Github\Client
     */
    private $client;


    /**
     * PullRequest constructor.
     *
     * @param string $requestPayload
     * @param \T3G\Intercept\Github\Client $client
     * @throws \T3G\Intercept\Exception\DoNotCareException
     */
    public function __construct(string $requestPayload, Client $client = null)
    {
        $this->client = $client ?: new Client();
        $fullPullRequestInformation = json_decode($requestPayload, true);
        if($fullPullRequestInformation['action'] !== 'opened') {
            throw new DoNotCareException();
        }
        $this->branch = $fullPullRequestInformation['pull_request']['base']['ref'];
        $this->diffUrl = $fullPullRequestInformation['pull_request']['diff_url'];
        $this->userUrl = $fullPullRequestInformation['pull_request']['user']['url'];
        $this->issueUrl = $fullPullRequestInformation['pull_request']['issue_url'];
        $this->pullRequestUrl = $fullPullRequestInformation['pull_request']['url'];
        $this->commentsUrl = $fullPullRequestInformation['pull_request']['comments_url'];
    }

    public function getClosePullRequestData()
    {
        return [
            'state' => 'closed'
        ];
    }

    public function getClosePullRequestComment()
    {
        $comment = 'Thank you for your contribution to TYPO3. We are using Gerrit Code Review for our contributions and' .
                   ' took the liberty to convert your pull request to a review in our review system.' . "\n";
        if (preg_match('/(?<reviewUrl>https\:\/\/review\.typo3\.org\/\d+)/m', $GLOBALS['gitOutput'], $matches) > 0) {
            $comment .= 'You can find your patch at: ' . $matches['reviewUrl'] . "\n";
        }
        $comment .= 'For further information on how to contribute have a look at ' . self::CONTRIB_GUIDE;
        return $comment;
    }

    public function closePullRequest()
    {
        $this->client->patch($this->pullRequestUrl, $this->getClosePullRequestData());
        $this->client->post(
            $this->commentsUrl,
            ['body' => $this->getClosePullRequestComment()]
        );
        $lockUrl = $this->issueUrl . '/lock';
        $this->client->put($lockUrl);
    }

    public function getIssueData() : array
    {
        $issueResponse = $this->client->get($this->issueUrl);
        $issueInformation = new IssueInformation();
        return $issueInformation->transformResponse($issueResponse);
    }

    public function getUserData()
    {
        $userResponse = $this->client->get($this->userUrl);
        $userInformation = new UserInformation();
        return $userInformation->transformResponse($userResponse);
    }
}