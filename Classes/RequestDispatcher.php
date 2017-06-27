<?php
declare(strict_types = 1);

namespace T3G\Intercept;

use Monolog\Logger;

/**
 * Class RequestDispatcher
 *
 * Dispatches to controller->actions depending on $_REQUEST parameters
 *
 * @package T3G\Intercept
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

    public function __construct(
        InterceptController $interceptController = null,
        GithubToGerritController $githubToGerritController = null,
        Logger $logger = null,
        GitSubtreeSplitController $gitSubtreeSplitController = null
    ) {
        $this->interceptController = $interceptController ?: new InterceptController();
        $this->githubToGerritController = $githubToGerritController ?: new GithubToGerritController();
        $this->setLogger($logger);
        $this->gitSubtreeSplitController = $gitSubtreeSplitController ?: new GitSubtreeSplitController();
    }

    public function dispatch()
    {
        try {
            if (!empty($_GET['github'])) {
                $this->githubToGerritController->transformPullRequestToGerritReview(file_get_contents("php://input"));
            } elseif (!empty($_GET['gitsplit'])) {
                $this->gitSubtreeSplitController->split(file_get_contents("php://input"));
            } else {
                if (!empty($_POST['payload'])) {
                    $this->interceptController->postBuildAction();
                } else if (!empty($_POST['changeUrl']) && !empty($_POST['patchset']) && !empty($_POST['branch'])) {
                    $this->interceptController->newBuildAction();
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
}