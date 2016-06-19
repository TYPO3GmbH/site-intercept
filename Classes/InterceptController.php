<?php
declare(strict_types = 1);

namespace T3G\Intercept;

class InterceptController
{

    /**
     * @codeCoverageIgnore glue code only
     */
    public function postBuildAction()
    {
        $slackMessageParser = new SlackMessageParser();
        $buildKey = $slackMessageParser->parseMessage();

        $bambooInformationRequestBuilder = new BambooStatusInformation();
        $buildStatusInformation = $bambooInformationRequestBuilder->transform($buildKey);

        $gerritInformer = new GerritInformer();
        $gerritInformer->voteOnGerrit($buildStatusInformation);
    }
}