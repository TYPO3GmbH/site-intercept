<?php
declare(strict_types = 1);

namespace T3G\Intercept\Tests\Unit\Github;

use Psr\Http\Message\ResponseInterface;
use T3G\Intercept\Github\IssueInformation;

class IssueInformationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @return void
     */
    public function transformSetsIssueTitleAndBody()
    {
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $responseProphecy->getBody()->willReturn(file_get_contents(BASEPATH . '/Tests/Fixtures/GithubIssueInformation.json'));
        $issueInformation = new IssueInformation();
        $result = $issueInformation->transformResponse($responseProphecy->reveal());

        self::assertSame('issue title', $result['title']);
        self::assertSame('updated body', $result['body']);
        self::assertSame('https://github.com/psychomieze/TYPO3.CMS/pull/1', $result['url']);
    }
}
