<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/build-information-service.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Intercept;

use Monolog\Logger;

/**
 * Class RequestDispatcher
 *
 * Dispatches to controller->actions depending on $_REQUEST parameters
 *
 */
class RequestDispatcher
{
    use Traits\Logger;

    /**
     * @var InterceptController
     */
    private $interceptController;

    /**
     * @var GithubToGerritController
     */
    private $githubToGerritController;

    /**
     * @var GitSubtreeSplitController
     */
    private $gitSubtreeSplitController;

    /**
     * @var DocumentationRenderingController
     */
    private $documentationRenderingController;

    /**
     * @var string
     */
    protected $payloadStream = 'php://input';

    public function __construct(
        InterceptController $interceptController = null,
        GithubToGerritController $githubToGerritController = null,
        Logger $logger = null,
        GitSubtreeSplitController $gitSubtreeSplitController = null,
        DocumentationRenderingController $documentationRenderingController = null
    ) {
        $this->interceptController = $interceptController ?: new InterceptController();
        $this->githubToGerritController = $githubToGerritController ?: new GithubToGerritController();
        $this->setLogger($logger);
        $this->gitSubtreeSplitController = $gitSubtreeSplitController ?: new GitSubtreeSplitController();
        $this->documentationRenderingController = $documentationRenderingController ?: new DocumentationRenderingController();
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
            } elseif (!empty($_GET['gitsplit'])) {
                $this->gitSubtreeSplitController->split(file_get_contents($this->payloadStream));
            } else {
                if (!empty($_POST['payload'])) {
                    $this->interceptController->postBuildAction();
                } else {
                    $this->logger->warning(
                        'Could not dispatch request. Request Data:' . "\n" . var_export($_REQUEST, true)
                    );
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('ERROR:"' . $e->getMessage() . '"" in ' . $e->getFile() . ' line ' . $e->getLine());
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
