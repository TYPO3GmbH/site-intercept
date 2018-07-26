<?php
declare(strict_types = 1);

namespace T3G\Intercept\Tests\Integration;

use PHPUnit\Framework\TestCase;
use T3G\Intercept\GithubToGerritController;

class GithubToGerritControllerTest extends TestCase
{

    /**
     * @test
     * @return void
     */
    public function githubToGerritIntegrationTest()
    {
        $payload = file_get_contents(BASEPATH . '/Tests/Fixtures/GithubPullRequestHookPayload.json');
        $githubToGerritController = new GithubToGerritController('/Volumes/CS/Sites/typo3.cms');
        $githubToGerritController->transformPullRequestToGerritReview($payload);
    }
}
