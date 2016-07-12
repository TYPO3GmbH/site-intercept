<?php

declare(strict_types = 1);

namespace T3G\Intercept\Tests\Unit\Github;

use T3G\Intercept\Github\PullRequestInformation;

class PullRequestInformationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $requestPayload = '';

    /**
     * @var \T3G\Intercept\Github\PullRequestInformation
     */
    protected $githubPullRequestInformation;

    public function setUp()
    {
        $this->requestPayload = file_get_contents(BASEPATH . '/Tests/Fixtures/GithubPullRequestHookPayload.json');
        $this->githubPullRequestInformation = new PullRequestInformation();
    }

    /**
     * @test
     * @return void
     */
    public function transformExtractsPatchUrl()
    {
        $result = $this->githubPullRequestInformation->transform($this->requestPayload);
        self::assertSame('https://github.com/psychomieze/TYPO3.CMS/pull/1.diff', $result['diffUrl']);
    }

    /**
     * @test
     * @return void
     */
    public function transformExtractsIssueUrl()
    {
        $result = $this->githubPullRequestInformation->transform($this->requestPayload);
        self::assertSame('https://api.github.com/repos/psychomieze/TYPO3.CMS/issues/1', $result['issueUrl']);
    }

    /**
     * @test
     * @return void
     */
    public function transformExtractsUserUrl()
    {
        $result = $this->githubPullRequestInformation->transform($this->requestPayload);
        self::assertSame('https://api.github.com/users/psychomieze', $result['userUrl']);
    }
}
