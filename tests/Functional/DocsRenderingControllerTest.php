<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Functional;

use App\Entity\DocumentationJar;
use App\Extractor\DeploymentInformation;
use App\Repository\DocumentationJarRepository;
use App\Service\GithubService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Symfony\Bridge\PhpUnit\ClockMock;
use T3G\LibTestHelper\Database\DatabasePrimer;
use T3G\LibTestHelper\Request\AssertRequestTrait;
use T3G\LibTestHelper\Request\MockRequest;
use T3G\LibTestHelper\Request\RequestExpectation;
use T3G\LibTestHelper\Request\RequestPool;

class DocsRenderingControllerTest extends AbstractFunctionalWebTestCase
{
    use DatabasePrimer;
    use AssertRequestTrait;
    private DocumentationJarRepository $documentationJarRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->prime();
        $this->documentationJarRepository = $this->getEntityManager()->getRepository(DocumentationJar::class);

        ClockMock::register(DeploymentInformation::class);
        ClockMock::register(GithubService::class);
        ClockMock::withClockMock(155309515.6937);
    }

    public function testGithubBuildIsNotTriggeredWithNewRepo(): void
    {
        $generalClient = $this->createMock(Client::class);
        $generalClient
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'https://raw.githubusercontent.com/TYPO3-Documentation/TYPO3CMS-Reference-CoreApi/latest/composer.json')
            ->willReturn(new Response(200, [], file_get_contents(__DIR__ . '/Fixtures/DocsToBambooGoodRequestComposer.json')));

        $slackClient = $this->createMock(Client::class);
        $slackClient->expects(self::once())->method('request')->with('POST', self::anything())->willReturn(new Response(200));

        $githubClient = $this->createMock(Client::class);
        $githubClient->expects(self::never())->method('request');

        $response = (new MockRequest($this->client))
            ->withMock('guzzle.client.slack', $slackClient)
            ->withMock('guzzle.client.general', $generalClient)
            ->withMock('guzzle.client.github', $githubClient)
            ->execute(require __DIR__ . '/Fixtures/DocsToBambooGoodRequest.php');
        self::assertEquals(204, $response->getStatusCode());
    }

    public function testGithubBuildIsTriggered(): void
    {
        $generalClient = $this->createMock(Client::class);
        $generalClient
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'https://raw.githubusercontent.com/TYPO3-Documentation/TYPO3CMS-Reference-CoreApi/latest/composer.json')
            ->willReturn(new Response(200, [], file_get_contents(__DIR__ . '/Fixtures/DocsToBambooGoodRequestComposer.json')));

        $slackClient = $this->createMock(Client::class);
        $slackClient->expects(self::once())->method('request')->with('POST', self::anything())->willReturn(new Response(200));

        $response = (new MockRequest($this->client))
            ->withMock('guzzle.client.slack', $slackClient)
            ->withMock('guzzle.client.general', $generalClient)
            ->execute(require __DIR__ . '/Fixtures/DocsToBambooGoodRequest.php');
        self::assertEquals(204, $response->getStatusCode());

        $this->rebootState();

        $jar = $this->documentationJarRepository->findOneBy(['packageName' => 'johndoe/make-good']);
        $jar->setApproved(true);
        $this->getEntityManager()->persist($jar);
        $this->getEntityManager()->flush();

        $generalClient = $this->createMock(Client::class);
        $generalClient
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'https://raw.githubusercontent.com/TYPO3-Documentation/TYPO3CMS-Reference-CoreApi/latest/composer.json')
            ->willReturn(new Response(200, [], file_get_contents(__DIR__ . '/Fixtures/DocsToBambooGoodRequestComposer.json')));

        $githubClient = $this->createMock(Client::class);
        $githubClient
            ->expects(self::once())
            ->method('request')
            ->with('POST', '/repos/TYPO3-Documentation/t3docs-ci-deploy/dispatches', self::anything())
            ->willReturn(new Response());

        $response = (new MockRequest($this->client))
            ->withMock('guzzle.client.slack', $slackClient)
            ->withMock('guzzle.client.general', $generalClient)
            ->withMock('guzzle.client.github', $githubClient)
            ->execute(require __DIR__ . '/Fixtures/DocsToBambooGoodRequest.php');
        self::assertEquals(204, $response->getStatusCode());
    }

    public function testGithubBuildForMultipleBranchesIsTriggered(): void
    {
        $requestPool = new RequestPool(
            new RequestExpectation(
                'GET',
                'https://bitbucket.org/pathfindermediagroup/eso-export-addon/raw/main/composer.json',
                new Response(200, [], file_get_contents(__DIR__ . '/Fixtures/DocsToBambooGoodMultiBranchRequestComposer.json'))
            ),
            new RequestExpectation(
                'GET',
                'https://bitbucket.org/pathfindermediagroup/eso-export-addon/raw/v1.1/composer.json',
                new Response(200, [], file_get_contents(__DIR__ . '/Fixtures/DocsToBambooGoodMultiBranchRequestComposer.json'))
            )
        );
        $generalClient = $this->createMock(Client::class);
        static::assertRequests($generalClient, $requestPool);

        $slackClient = $this->createMock(Client::class);
        $slackClient->expects(self::once())->method('request')->with('POST', self::anything())->willReturn(new Response(200));

        $response = (new MockRequest($this->client))
            ->withMock('guzzle.client.slack', $slackClient)
            ->withMock('guzzle.client.general', $generalClient)
            ->execute(require __DIR__ . '/Fixtures/DocsToBambooGoodRequestMultiBranch.php');
        self::assertEquals(204, $response->getStatusCode());

        $this->rebootState();

        $jars = $this->documentationJarRepository->findBy(['packageName' => 'bla/yay']);
        foreach ($jars as $jar) {
            $jar->setApproved(true);
            $this->entityManager->persist($jar);
        }
        $this->entityManager->flush();

        $requestPool = new RequestPool(
            new RequestExpectation(
                'GET',
                'https://bitbucket.org/pathfindermediagroup/eso-export-addon/raw/main/composer.json',
                new Response(200, [], file_get_contents(__DIR__ . '/Fixtures/DocsToBambooGoodMultiBranchRequestComposer.json'))
            ),
            new RequestExpectation(
                'GET',
                'https://bitbucket.org/pathfindermediagroup/eso-export-addon/raw/v1.1/composer.json',
                new Response(200, [], file_get_contents(__DIR__ . '/Fixtures/DocsToBambooGoodMultiBranchRequestComposer.json'))
            )
        );
        $generalClient = $this->createMock(Client::class);
        static::assertRequests($generalClient, $requestPool);

        $requestPool = new RequestPool(
            new RequestExpectation(
                'POST',
                '/repos/TYPO3-Documentation/t3docs-ci-deploy/dispatches',
                new Response(200)
            ),
            new RequestExpectation(
                'POST',
                '/repos/TYPO3-Documentation/t3docs-ci-deploy/dispatches',
                new Response(200)
            )
        );
        $githubClient = $this->createMock(Client::class);
        static::assertRequests($githubClient, $requestPool);

        $response = (new MockRequest($this->client))
            ->withMock('guzzle.client.slack', $slackClient)
            ->withMock('guzzle.client.general', $generalClient)
            ->withMock('guzzle.client.github', $githubClient)
            ->execute(require __DIR__ . '/Fixtures/DocsToBambooGoodRequestMultiBranch.php');
        self::assertEquals(204, $response->getStatusCode());
    }

    public function testGithubBuildIsNotTriggered(): void
    {
        $slackClient = $this->createMock(Client::class);
        $slackClient->expects(self::never())->method('request')->with('POST', self::anything());

        $githubClient = $this->createMock(Client::class);
        $githubClient->expects(self::never())->method('request');

        $response = (new MockRequest($this->client))
            ->withMock('guzzle.client.slack', $slackClient)
            ->withMock('guzzle.client.github', $githubClient)
            ->execute(require __DIR__ . '/Fixtures/DocsToBambooBadRequest.php');
        self::assertEquals(412, $response->getStatusCode());
    }

    public function testGithubBuildIsNotTriggeredDueToMissingDependency(): void
    {
        $slackClient = $this->createMock(Client::class);
        $slackClient->expects(self::never())->method('request')->with('POST', self::anything());

        $generalClient = $this->createMock(Client::class);
        $generalClient
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'https://raw.githubusercontent.com/TYPO3-Documentation/TYPO3CMS-Reference-CoreApi/latest/composer.json')
            ->willReturn(new Response(200, [], file_get_contents(__DIR__ . '/Fixtures/DocsToBambooBadRequestComposerWithoutDependency.json')));

        $response = (new MockRequest($this->client))
            ->withMock('guzzle.client.slack', $slackClient)
            ->withMock('guzzle.client.general', $generalClient)
            ->execute(require __DIR__ . '/Fixtures/DocsToBambooGoodRequest.php');

        self::assertSame('Dependencies are not fulfilled. See https://intercept.typo3.com for more information.', $response->getContent());
        self::assertSame(412, $response->getStatusCode());
    }

    /**
     * cms-core can not require cms-core in its composer.json.
     */
    public function testGithubBuildIsTriggeredForPackageThatCanNotRequireItself(): void
    {
        $slackClient = $this->createMock(Client::class);
        $slackClient->expects(self::once())->method('request')->with('POST', self::anything())->willReturn(new Response(200, [], ''));

        $generalClient = $this->createMock(Client::class);
        $generalClient
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'https://raw.githubusercontent.com/TYPO3-Documentation/TYPO3CMS-Reference-CoreApi/latest/composer.json')
            ->willReturn(new Response(200, [], file_get_contents(__DIR__ . '/Fixtures/DocsToBambooGoodRequestComposerWithoutDependencyForSamePackage.json')));

        $response = (new MockRequest($this->client))
            ->withMock('guzzle.client.slack', $slackClient)
            ->withMock('guzzle.client.general', $generalClient)
            ->execute(require __DIR__ . '/Fixtures/DocsToBambooGoodRequest.php');

        self::assertSame(204, $response->getStatusCode());

        $this->rebootState();

        $jar = $this->documentationJarRepository->findOneBy(['packageName' => 'typo3/cms-core']);
        $jar->setApproved(true);
        $this->getEntityManager()->persist($jar);
        $this->getEntityManager()->flush();

        $generalClient = $this->createMock(Client::class);
        $generalClient
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'https://raw.githubusercontent.com/TYPO3-Documentation/TYPO3CMS-Reference-CoreApi/latest/composer.json')
            ->willReturn(new Response(200, [], file_get_contents(__DIR__ . '/Fixtures/DocsToBambooGoodRequestComposerWithoutDependencyForSamePackage.json')));

        $githubClient = $this->createMock(Client::class);
        $githubClient
            ->expects(self::once())
            ->method('request')
            ->with('POST', '/repos/TYPO3-Documentation/t3docs-ci-deploy/dispatches', self::anything())
            ->willReturn(new Response());

        $response = (new MockRequest($this->client))
            ->withMock('guzzle.client.slack', $slackClient)
            ->withMock('guzzle.client.general', $generalClient)
            ->withMock('guzzle.client.github', $githubClient)
            ->execute(require __DIR__ . '/Fixtures/DocsToBambooGoodRequest.php');

        self::assertSame(204, $response->getStatusCode());
    }

    public function testGithubBuildIsNotTriggeredDueToDeletedBranch(): void
    {
        $slackClient = $this->createMock(Client::class);
        $slackClient->expects(self::never())->method('request')->with('POST', self::anything());

        $response = (new MockRequest($this->client))
            ->withMock('guzzle.client.slack', $slackClient)
            ->execute(require __DIR__ . '/Fixtures/DocsToBambooGithubDeletedBranchRequest.php');

        self::assertSame('The branch in this push event has been deleted.', $response->getContent());
        self::assertSame(412, $response->getStatusCode());
    }

    public function testGithubPingIsHandled(): void
    {
        $slackClient = $this->createMock(Client::class);
        $slackClient->expects(self::never())->method('request')->with('POST', self::anything());

        $response = (new MockRequest($this->client))
            ->withMock('guzzle.client.slack', $slackClient)
            ->execute(require __DIR__ . '/Fixtures/DocsToBambooGithubPingRequest.php');

        self::assertEquals(200, $response->getStatusCode());
        self::assertStringContainsString('github ping', $response->getContent());
    }
}
