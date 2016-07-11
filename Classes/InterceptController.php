<?php
declare(strict_types = 1);

namespace T3G\Intercept;

use T3G\Intercept\Bamboo\Request;
use T3G\Intercept\Bamboo\StatusInformation;
use T3G\Intercept\Gerrit\Informer;
use T3G\Intercept\Slack\MessageParser;

/**
 * Class InterceptController
 *
 * @package T3G\Intercept
 */
class InterceptController
{

    /**
     * @var Request
     */
    private $bambooRequests;

    /**
     * @var \T3G\Intercept\Slack\MessageParser
     */
    private $slackMessageParser;

    /**
     * @var \T3G\Intercept\Bamboo\StatusInformation
     */
    private $bambooStatusInformation;

    /**
     * @var \T3G\Intercept\Gerrit\Informer
     */
    private $gerritInformer;

    public function __construct(
        Request $bambooRequests = null,
        MessageParser $slackMessageParser = null,
        StatusInformation $bambooStatusInformation = null,
        Informer $gerritInformer = null
    ) {
        $this->bambooRequests = $bambooRequests ?: new Request();
        $this->slackMessageParser = $slackMessageParser ?: new MessageParser();
        $this->bambooStatusInformation = $bambooStatusInformation ?: new StatusInformation();
        $this->gerritInformer = $gerritInformer ?: new Informer();
    }

    /**
     * Action to execute after a new patchset was uploaded to gerrit
     * Is triggered by the gerrit patchset-created hook
     */
    public function newBuildAction()
    {
        $changeUrl = $_POST['changeUrl'];
        $patchSet = (int)$_POST['patchset'];
        if ($_POST['branch'] === 'master') {
            $this->bambooRequests->triggerNewCoreBuild($changeUrl, $patchSet);
        }
    }

    /**
     * Action to execute after a build was finished
     * We are notified via a slack message hook
     */
    public function postBuildAction()
    {
        $buildKey = $this->slackMessageParser->parseMessage();
        $buildStatusInformation = $this->bambooStatusInformation->transform($buildKey);
        $this->gerritInformer->voteOnGerrit($buildStatusInformation);
    }
}