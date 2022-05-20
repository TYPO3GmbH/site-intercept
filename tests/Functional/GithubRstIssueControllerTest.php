<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Functional;

use App\Bundle\TestDoubleBundle;
use App\Client\GeneralClient;
use App\Kernel;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class GithubRstIssueControllerTest extends AbstractFunctionalWebTestCase
{
    use ProphecyTrait;

    protected function setUp(): void
    {
        self::bootKernel();
    }

    /**
     * @test
     */
    public function githubIssueIsCreatedForRstChanges(): void
    {
        /** @var Response $rstFetchRawResponse */
        $rstFetchRawResponse = require __DIR__ . '/Fixtures/GithubRstIssuePatchFetchRawFileResponse.php';
        $generalClientProphecy = $this->prophesize(GeneralClient::class);
        $generalClientProphecy->request(
            'GET',
            'https://raw.githubusercontent.com/TYPO3/TYPO3.CMS/1b5272038f09dd6f9d09736c8f57172c37d33648/typo3/sysext/core/Documentation/Changelog/12.0/Feature-97326-OpenBackendPageFromAdminPanel.rst'
        )->shouldBeCalled()->willReturn($rstFetchRawResponse);
        $generalClientProphecy->request(
            'GET',
            'https://raw.githubusercontent.com/TYPO3/TYPO3.CMS/1b5272038f09dd6f9d09736c8f57172c37d33648/typo3/sysext/rte_ckeditor/Documentation/Configuration/ConfigureTypo3.rst'
        )->shouldNotBeCalled();
        $generalClientProphecy->request(
            'GET',
            'https://raw.githubusercontent.com/TYPO3/TYPO3.CMS/1b5272038f09dd6f9d09736c8f57172c37d33648/typo3/sysext/core/Documentation/Index.rst'
        )->shouldNotBeCalled();
        $generalClientProphecy->request(
            'POST',
            'https://api.github.com/repos/foobar-documentation/Changelog-To-Doc/issues',
            [
                'headers' => [
                    'Authorization' => 'token 4711',
                ],
                'json' => [
                    'title' => '[BUGFIX] Load AdditionalFactoryConfiguration.php again',
                    'body' => ":information_source: View this commit [on Github](https://github.com/TYPO3/TYPO3.CMS/commit/1b93464c68d398351410d871826e30066bfdbb2f)\n"
                        . ":busts_in_silhouette: Authored by Mathias Brodala mbrodala@pagemachine.de\n"
                        . ":heavy_check_mark: Merged by Anja Leichsenring aleichsenring@ab-softlab.de\n"
                        . "\n"
                        . "## Commit message\n"
                        . "\n"
                        . "[BUGFIX] Load AdditionalFactoryConfiguration.php again\n"
                        . "\n"
                        . "This file is placed in \"typo3conf\" just like the other configuration\n"
                        . "files and must be loaded accordingly.\n"
                        . "\n"
                        . "Resolves: #87035\n"
                        . "Relates: #85560\n"
                        . "Releases: main\n"
                        . "Change-Id: I7db72a3c1b29f79fb242f1e5da21ec7d77614bfe\n"
                        . "Reviewed-on: https://review.typo3.org/58977\n"
                        . "Tested-by: TYPO3com <no-reply@typo3.com>\n"
                        . "Reviewed-by: Andreas Wolf <andreas.wolf@typo3.org>\n"
                        . "Reviewed-by: Benni Mack <benni@typo3.org>\n"
                        . "Tested-by: Benni Mack <benni@typo3.org>\n"
                        . "Reviewed-by: Anja Leichsenring <aleichsenring@ab-softlab.de>\n"
                        . "Tested-by: Anja Leichsenring <aleichsenring@ab-softlab.de>\n"
                        . "\n"
                        . "## :heavy_plus_sign: Added files\n"
                        . "\n"
                        . "<details>\n"
                        . "<summary>12.0/Feature-97326-OpenBackendPageFromAdminPanel.rst</summary>\n"
                        . "\n"
                        . "\n"
                        . "```rst\n"
                        . (string)$rstFetchRawResponse->getBody()
                        . "\n"
                        . "```\n"
                        . "\n"
                        . "</details>\n"
                        . "\n"
                        . "## :heavy_division_sign: Modified files\n"
                        . "\n"
                        . "<details>\n"
                        . "<summary>12.0/Feature-97326-OpenBackendPageFromAdminPanel.rst</summary>\n"
                        . "\n"
                        . "\n"
                        . "```rst\n"
                        . (string)$rstFetchRawResponse->getBody()
                        . "\n"
                        . "```\n"
                        . "\n"
                        . "</details>\n"
                        . "\n"
                        . "## :heavy_minus_sign: Removed files\n"
                        . "\n"
                        . "<details>\n"
                        . "<summary>12.0/Feature-97326-OpenBackendPageFromAdminPanel.rst</summary>\n"
                        . "\n"
                        . "\n"
                        . "```rst\n"
                        . (string)$rstFetchRawResponse->getBody()
                        . "\n"
                        . "```\n"
                        . "\n"
                        . "</details>\n",
                    'labels' => ['12.0'],
                ]
            ]
        )->shouldBeCalled();
        TestDoubleBundle::addProphecy(GeneralClient::class, $generalClientProphecy);

        $kernel = new Kernel('test', true);
        $kernel->boot();

        $request = require __DIR__ . '/Fixtures/GithubRstIssuePatchRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }

    /**
     * @test
     */
    public function githubIssueIsNotCreatedForChangesInNonMainBranch(): void
    {
        $generalClientProphecy = $this->prophesize(GeneralClient::class);
        $generalClientProphecy->request(Argument::cetera())->shouldNotBeCalled();
        TestDoubleBundle::addProphecy(GeneralClient::class, $generalClientProphecy);

        $kernel = new Kernel('test', true);
        $kernel->boot();

        $request = require __DIR__ . '/Fixtures/GithubRstIssuePatchBackportRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }

    /**
     * @test
     */
    public function githubIssueIsNotCreatedForChangesWithoutDocsChanges(): void
    {
        $generalClientProphecy = $this->prophesize(GeneralClient::class);
        $generalClientProphecy->request(Argument::cetera())->shouldNotBeCalled();
        TestDoubleBundle::addProphecy(GeneralClient::class, $generalClientProphecy);

        $kernel = new Kernel('test', true);
        $kernel->boot();

        $request = require __DIR__ . '/Fixtures/GithubRstIssuePatchNoDocsChangesRequest.php';
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
    }
}
