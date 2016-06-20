<?php
declare(strict_types = 1);

namespace T3G\Intercept;

use T3G\Intercept\Library\CurlBambooRequests;

class InterceptController
{

    /**
     * @var \T3G\Intercept\Library\CurlBambooRequests
     */
    private $bambooRequests;
    /**
     * @var \T3G\Intercept\SlackMessageParser
     */
    private $slackMessageParser;
    /**
     * @var \T3G\Intercept\BambooStatusInformation
     */
    private $bambooStatusInformation;
    /**
     * @var \T3G\Intercept\GerritInformer
     */
    private $gerritInformer;

    public function __construct(
        CurlBambooRequests $bambooRequests = null,
        SlackMessageParser $slackMessageParser = null,
        BambooStatusInformation $bambooStatusInformation = null,
        GerritInformer $gerritInformer = null
    )
    {
        $this->bambooRequests = $bambooRequests ?: new CurlBambooRequests();
        $this->slackMessageParser = $slackMessageParser ?: new SlackMessageParser();
        $this->bambooStatusInformation = $bambooStatusInformation ?: new BambooStatusInformation();
        $this->gerritInformer = $gerritInformer ?: new GerritInformer();
    }

    /**
     * Action to execute after a new patchset was uploaded to gerrit
     * Is triggered by the gerrit patchset-created hook
     */
    public function newBuildAction()
    {
        $changeUrl = $_GET['changeUrl'];
        $patchSet = (int)$_GET['patchset'];
        $this->bambooRequests->triggerNewCoreBuild($changeUrl, $patchSet);
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