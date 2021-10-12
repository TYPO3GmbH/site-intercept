<?php

declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Functional\AdminInterface\Docs;

use App\Bundle\TestDoubleBundle;
use App\Client\GithubClient;
use App\Tests\Functional\AbstractFunctionalWebTestCase;
use App\Tests\Functional\DatabasePrimer;
use App\Tests\Functional\Fixtures\AdminInterface\Docs\DeploymentsControllerTestData;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\BrowserKit\AbstractBrowser;

class DeploymentsControllerTest extends AbstractFunctionalWebTestCase
{
    use ProphecyTrait;

    /**
     * @var AbstractBrowser
     */
    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        TestDoubleBundle::reset();
        $this->addRabbitManagementClientProphecy();
    }

    /**
     * @test
     */
    public function indexRenderTableWithEntries(): void
    {
        $this->client = static::createClient();
        $this->manualSetup();
        $this->logInAsDocumentationMaintainer($this->client);
        $this->client->request('GET', '/admin/docs/deployments');
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('<table class="datatable-table">', $content);
        $this->assertStringContainsString('typo3/docs-homepage', $content);
    }

    /**
     * @test
     */
    public function deleteRenderTableEntries(): void
    {
        $this->addRabbitManagementClientProphecy();
        $this->addRabbitManagementClientProphecy();
        $this->addRabbitManagementClientProphecy();

        $this->createPlainGithubProphecy();
        $this->client = static::createClient();
        $this->manualSetup();
        $this->logInAsDocumentationMaintainer($this->client);

        $this->client->request('GET', '/admin/docs/deployments');
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('typo3/docs-homepage', $content);

        $crawler = $this->client->request('GET', '/admin/docs/deployments/delete/1/confirm');
        $github = $this->prophesize(GithubClient::class);
        $github->post(Argument::cetera())->willReturn(new Response());
        TestDoubleBundle::addProphecy(GithubClient::class, $github);

        $form = $crawler->selectButton('docsformdelete')->form();
        $this->client->submit($form, [], []);
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());

        $this->createPlainGithubProphecy();
        $this->logInAsDocumentationMaintainer($this->client);

        $this->client->request('GET', '/admin/docs/deployments');

        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Deleting', $content);
    }

    /**
     * @test
     */
    public function approveEntry(): void
    {
        $this->addRabbitManagementClientProphecy();
        $this->addRabbitManagementClientProphecy();
        $this->addRabbitManagementClientProphecy();

        $this->client = static::createClient();
        $this->manualSetup();
        $this->logInAsDocumentationMaintainer($this->client);

        $this->client->request('GET', '/admin/docs/deployments');
        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Approve documentation', $content);

        $github = $this->prophesize(GithubClient::class);
        $github->post(Argument::cetera())->willReturn(new Response());
        TestDoubleBundle::addProphecy(GithubClient::class, $github);
        $this->client->request('GET', '/admin/docs/deployments/approve/10');
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());

        $this->logInAsDocumentationMaintainer($this->client);

        $this->client->request('GET', '/admin/docs/deployments');

        $content = $this->client->getResponse()->getContent();
        $this->assertStringNotContainsString('Approve documentation', $content);
    }


    private function createPlainGithubProphecy(): void
    {
        $github = $this->prophesize(GithubClient::class);
        TestDoubleBundle::addProphecy(GithubClient::class, $github);
        $github->get(Argument::any(), Argument::cetera())->willReturn(
            new Response(200, [], json_encode([]))
        );
    }


    private function manualSetup(): void
    {
        DatabasePrimer::prime(self::$kernel);
        (new DeploymentsControllerTestData())->load(
            self::$kernel->getContainer()->get('doctrine')->getManager()
        );
    }
}
