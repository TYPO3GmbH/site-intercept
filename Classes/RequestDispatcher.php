<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/build-information-service.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Intercept;

/**
 * Class RequestDispatcher
 *
 * Dispatches to controller->actions depending on $_REQUEST parameters
 *
 */
class RequestDispatcher
{
    /**
     * @var GithubToGerritController
     */
    private $githubToGerritController;

    /**
     * @var string
     */
    protected $payloadStream = 'php://input';

    public function __construct(
        GithubToGerritController $githubToGerritController = null
    ) {
        $this->githubToGerritController = $githubToGerritController ?: new GithubToGerritController();
    }

    public function dispatch()
    {
        try {
            if (!empty($_GET['github'])) {
                // See if gerrit pull request is called here
                $payload = json_decode(file_get_contents($this->payloadStream), true);
                if (!empty($payload['action']) && $payload['action'] === 'opened' && !empty($payload['pull_request'])) {
                    $this->githubToGerritController->transformPullRequestToGerritReview(file_get_contents($this->payloadStream));
                }
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * Used for testing.
     *
     * @internal
     */
    public function setPayloadStream(string $payloadStream)
    {
        $this->payloadStream = $payloadStream;
    }
}
