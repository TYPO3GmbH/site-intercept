<?php
declare(strict_types = 1);
namespace T3G\Intercept\Tests\Unit;

use PHPUnit\Framework\TestCase;
use T3G\Intercept\Bamboo\Client;
use T3G\Intercept\Bamboo\BambooBuildStatusExtractor;
use T3G\Intercept\Gerrit\Informer;
use T3G\Intercept\InterceptController;
use T3G\Intercept\Slack\MessageParser;

class InterceptControllerTest extends TestCase
{
    /**
     * @test
     * @return void
     */
    public function postBuildActionVotesOnGerrit()
    {
        $buildKey = 'CORE-GTC-48';
        $buildStatusInformation = ['successful' => true];
        $slackMessageParser = $this->prophesize(MessageParser::class);
        $bambooStatusInformation = $this->prophesize(BambooBuildStatusExtractor::class);
        $gerritInformer = $this->prophesize(Informer::class);

        $slackMessageParser->parseMessage()->willReturn($buildKey);
        $bambooStatusInformation->transform($buildKey)->willReturn($buildStatusInformation);

        $interceptController = new InterceptController(
            null,
            $slackMessageParser->reveal(),
            $bambooStatusInformation->reveal(),
            $gerritInformer->reveal()
        );

        $interceptController->postBuildAction();
        $gerritInformer->voteOnGerrit($buildStatusInformation)->shouldHaveBeenCalled();
    }

}
