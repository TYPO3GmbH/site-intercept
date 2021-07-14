<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Functional\AdminInterface;

use App\Bundle\TestDoubleBundle;
use App\Client\GraylogClient;
use App\Client\RabbitManagementClient;
use App\Tests\Functional\AbstractFunctionalWebTestCase;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

class SplitCoreControllerTest extends AbstractFunctionalWebTestCase
{
    use ProphecyTrait;
    private $graylogProphecy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->graylogProphecy = $this->addGraylogClientProphecy();
        $this->graylogProphecy->get(Argument::cetera())->willReturn(new Response(200, [], '{}'));


    }

    /**
     * @test
     */
    public function rabbitDataIsRendered()
    {
        TestDoubleBundle::addProphecy(AMQPStreamConnection::class, $this->prophesize(AMQPStreamConnection::class));

        $rabbitClientProphecy = $this->prophesize(RabbitManagementClient::class);
        TestDoubleBundle::addProphecy(RabbitManagementClient::class, $rabbitClientProphecy);
        $rabbitClientProphecy->get(Argument::cetera())->shouldBeCalled()->willReturn(
            new Response(200, [], json_encode(['consumers' => 1, 'messages' => 2]))
        );

        $client = static::createClient();
        $client->request('GET', '/admin/split/core');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('data-rabbit-status="online"', $client->getResponse()->getContent());
        $this->assertStringContainsString('data-rabbit-worker-status="online"', $client->getResponse()->getContent());
        $this->assertStringContainsString('data-rabbit-jobs="2"', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function rabbitDownIsRendered()
    {
        TestDoubleBundle::addProphecy(AMQPStreamConnection::class, $this->prophesize(AMQPStreamConnection::class));

        $rabbitClientProphecy = $this->prophesize(RabbitManagementClient::class);
        TestDoubleBundle::addProphecy(RabbitManagementClient::class, $rabbitClientProphecy);
        $rabbitClientProphecy->get(Argument::cetera())->shouldBeCalled()->willThrow(
            new ClientException('testing', new Request('GET', ''), new Response())
        );

        $client = static::createClient();
        $client->request('GET', '/admin/split/core');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertStringContainsString('data-rabbit-status="offline"', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function recentLogMessagesAreRendered()
    {
        TestDoubleBundle::reset();


        TestDoubleBundle::addProphecy(AMQPStreamConnection::class, $this->prophesize(AMQPStreamConnection::class));
        $rabbitClientProphecy = $this->prophesize(RabbitManagementClient::class);
        TestDoubleBundle::addProphecy(RabbitManagementClient::class, $rabbitClientProphecy);
        $rabbitClientProphecy->get(Argument::cetera())->shouldBeCalled()->willReturn(
            new Response(200, [], json_encode(['consumers' => 1, 'messages' => 2]))
        );

        $graylogClientProphecy = $this->prophesize(GraylogClient::class);
        TestDoubleBundle::addProphecy(GraylogClient::class, $graylogClientProphecy);
        // first ->get() call to elastic returns one 'queued' log entry
        $graylogClientProphecy->get(Argument::cetera())->shouldBeCalled()->willReturn(
            new Response(200, [], json_encode([
                'messages' => [
                    0 => [
                        'message' => [
                            'application' => 'intercept',
                            'env' => 'prod',
                            'level' => 6,
                            'message' => 'Queued a core split job to queue',
                            'ctxt_job_uuid' => 'a048046f-3204-45f6-9572-cb7af54ad7d5',
                            'ctxt_type' => 'patch',
                            'ctxt_status' => 'queued',
                            'ctxt_sourceBranch' => 'master',
                            'ctxt_targetBranch' => 'master',
                            'ctxt_triggeredBy' => 'api',
                            'timestamp' => '2019-03-11T10:33:16.803Z',
                        ]
                    ]
                ]
            ]))
        );
        // second ->get() call to elastic returns one detail log row and a done message
        $graylogClientProphecy->get(Argument::cetera())->shouldBeCalled()->willReturn(
            new Response(200, [], json_encode([
                'messages' => [
                    0 => [
                        'message' => [
                            'application' => 'intercept',
                            'env' => 'prod',
                            'level' => 6,
                            'message' => 'Git command error output: Everything up-to-date',
                            'ctxt_job_uuid' => 'a048046f-3204-45f6-9572-cb7af54ad7d5',
                            'ctxt_type' => 'patch',
                            'ctxt_status' => 'work',
                            'ctxt_sourceBranch' => 'master',
                            'ctxt_targetBranch' => 'master',
                            'timestamp' => '2019-03-11T10:34:22.803Z',
                        ]
                    ],
                    1 => [
                        'message' => [
                            'application' => 'intercept',
                            'env' => 'prod',
                            'level' => 6,
                            'message' => 'Finished a git split worker job',
                            'ctxt_job_uuid' => 'a048046f-3204-45f6-9572-cb7af54ad7d5',
                            'ctxt_type' => 'patch',
                            'ctxt_status' => 'done',
                            'ctxt_sourceBranch' => 'master',
                            'ctxt_targetBranch' => 'master',
                            'timestamp' => '2019-03-11T10:36:27.256Z',
                        ]
                    ]
                ]
            ]))
        );

        $client = static::createClient();
        $client->request('GET', '/admin/split/core');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertMatchesRegularExpression('/a048046f-3204-45f6-9572-cb7af54ad7d5/', $client->getResponse()->getContent());
        $this->assertMatchesRegularExpression('/Git command error output: Everything up-to-date/', $client->getResponse()->getContent());
    }

    /**
     * @test
     */
    public function splitCoreCanBeTriggered()
    {
        $graylog = $this->addGraylogClientProphecy();
        $graylog->get(Argument::cetera())->willReturn(new Response(200, [], '{}'));
        $rabbitClientProphecy = $this->prophesize(RabbitManagementClient::class);
        TestDoubleBundle::addProphecy(RabbitManagementClient::class, $rabbitClientProphecy);
        $rabbitClientProphecy->get(Argument::cetera())->willReturn(
            new Response(200, [], json_encode(['consumers' => 1, 'messages' => 2]))
        );
        $rabbitClientProphecy = $this->prophesize(RabbitManagementClient::class);
        TestDoubleBundle::addProphecy(RabbitManagementClient::class, $rabbitClientProphecy);
        $rabbitClientProphecy->get(Argument::cetera())->willReturn(
            new Response(200, [], json_encode(['consumers' => 1, 'messages' => 3]))
        );

        TestDoubleBundle::addProphecy(AMQPStreamConnection::class, $this->prophesize(AMQPStreamConnection::class));

        // rabbit stream client double for the second request
        $rabbitStreamProphecy = $this->prophesize(AMQPStreamConnection::class);
        TestDoubleBundle::addProphecy(AMQPStreamConnection::class, $rabbitStreamProphecy);
        $rabbitChannelProphecy = $this->prophesize(AMQPChannel::class);
        $rabbitStreamProphecy->channel()->shouldBeCalled()->willReturn($rabbitChannelProphecy->reveal());
        $rabbitChannelProphecy->queue_declare(Argument::cetera())->shouldBeCalled();
        $rabbitChannelProphecy->basic_publish(Argument::cetera())->shouldBeCalled();

        $client = static::createClient();
        $this->logInAsDocumentationMaintainer($client);
        $crawler = $client->request('GET', '/admin/split/core');

        // Get the rendered form, feed it with some data and submit it
        $form = $crawler->selectButton('Trigger master')->form();
        $client->submit($form, [], []);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // The branch is shown
        $this->assertMatchesRegularExpression(
            '/Triggered split job for core branch &quot;master&quot;/',
            $client->getResponse()->getContent()
        );
    }

    /**
     * @test
     */
    public function tagCoreCanBeTriggered()
    {
        $graylog = $this->addGraylogClientProphecy();
        $graylog->get(Argument::cetera())->willReturn(new Response(200, [], '{}'));
        $rabbitClientProphecy = $this->prophesize(RabbitManagementClient::class);
        TestDoubleBundle::addProphecy(RabbitManagementClient::class, $rabbitClientProphecy);
        $rabbitClientProphecy->get(Argument::cetera())->willReturn(
            new Response(200, [], json_encode(['consumers' => 1, 'messages' => 2]))
        );
        $rabbitClientProphecy = $this->prophesize(RabbitManagementClient::class);
        TestDoubleBundle::addProphecy(RabbitManagementClient::class, $rabbitClientProphecy);
        $rabbitClientProphecy->get(Argument::cetera())->willReturn(
            new Response(200, [], json_encode(['consumers' => 1, 'messages' => 3]))
        );

        TestDoubleBundle::addProphecy(AMQPStreamConnection::class, $this->prophesize(AMQPStreamConnection::class));

        // rabbit stream client double for the second request
        $rabbitStreamProphecy = $this->prophesize(AMQPStreamConnection::class);
        TestDoubleBundle::addProphecy(AMQPStreamConnection::class, $rabbitStreamProphecy);
        $rabbitChannelProphecy = $this->prophesize(AMQPChannel::class);
        $rabbitStreamProphecy->channel()->shouldBeCalled()->willReturn($rabbitChannelProphecy->reveal());
        $rabbitChannelProphecy->queue_declare(Argument::cetera())->shouldBeCalled();
        $rabbitChannelProphecy->basic_publish(Argument::cetera())->shouldBeCalled();

        $client = static::createClient();
        $this->logInAsDocumentationMaintainer($client);
        $crawler = $client->request('GET', '/admin/split/core');

        // Get the rendered form, feed it with some data and submit it
        $form = $crawler->selectButton('Trigger sub repo tagging')->form();
        $form['split_core_tag_form[tag]'] = 'v9.5.1';
        $client->submit($form, [], []);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // The tag is shown
        $this->assertMatchesRegularExpression(
            '/Triggered tag job with tag &quot;v9.5.1&quot;/',
            $client->getResponse()->getContent()
        );
    }
}
