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
        /** @var Response $rstFetchRawResponse */
        $rstFetchCompareResponse = require __DIR__ . '/Fixtures/GithubRstIssuePatchFetchCompareResponse.php';
        $generalClientProphecy = $this->prophesize(GeneralClient::class);
        $generalClientProphecy->request(
            'GET',
            'https://raw.githubusercontent.com/TYPO3/TYPO3.CMS/1b5272038f09dd6f9d09736c8f57172c37d33648/typo3/sysext/core/Documentation/Changelog/12.0/Feature-97326-OpenBackendPageFromAdminPanel.rst'
        )->shouldBeCalled()->willReturn($rstFetchRawResponse);
        $generalClientProphecy->request(
            'GET',
            'https://api.github.com/repos/TYPO3/TYPO3.CMS/compare/cc9ce47f0c030772ca6b59bbc65d728fb32c96dd...47520511f4947a6ebd139a84e831a062a5b61c31'
        )->shouldBeCalled()->willReturn($rstFetchCompareResponse);
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
                    'body' => file_get_contents(__DIR__ . '/Fixtures/GithubRstPushBody.txt'),
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
