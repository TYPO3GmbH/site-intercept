<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/build-information-service.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace T3G\Intercept;

use T3G\Intercept\Bamboo\Client;
use T3G\Intercept\Bamboo\StatusInformation;
use T3G\Intercept\Gerrit\Informer;
use T3G\Intercept\Slack\MessageParser;

/**
 * Class InterceptController
 *
 */
class InterceptController
{

    /**
     * @var Client
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
        Client $bambooRequests = null,
        MessageParser $slackMessageParser = null,
        StatusInformation $bambooStatusInformation = null,
        Informer $gerritInformer = null
    ) {
        $this->bambooRequests = $bambooRequests ?: new Client();
        $this->slackMessageParser = $slackMessageParser ?: new MessageParser();
        $this->bambooStatusInformation = $bambooStatusInformation ?: new StatusInformation();
        $this->gerritInformer = $gerritInformer ?: new Informer();
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
