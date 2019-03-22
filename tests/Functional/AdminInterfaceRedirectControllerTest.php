<?php
declare(strict_types = 1);
namespace App\Tests\Functional;

use App\Bundle\TestDoubleBundle;
use App\Client\BambooClient;
use App\Tests\Functional\Fixtures\AdminInterfaceRedirectControllerTestData;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class AdminInterfaceRedirectControllerTest extends WebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    private $client;

    public function setUp()
    {
        parent::setUp();
        self::bootKernel();
        DatabasePrimer::prime(self::$kernel);

        $this->client = static::createClient();
        (new AdminInterfaceRedirectControllerTestData())->load(
            self::$kernel->getContainer()->get('doctrine')->getManager()
        );
    }

    /**
     * @test
     */
    public function indexRenderTableWithRedirectEntries()
    {
        $this->logIn();
        $this->client->request('GET', '/redirect/');
        $content = $this->client->getResponse()->getContent();
        $this->assertContains('<table class="table table-sm table-striped table-bordered">', $content);
        $this->assertContains('/p/vendor/packageOld/1.0/Foo.html', $content);
        $this->assertContains('/p/vendor/packageNew/1.0/Foo.html', $content);
    }

    /**
     * @test
     */
    public function showRenderTableWithRedirectEntries()
    {
        $this->logIn();
        $this->client->request('GET', '/redirect/1');
        $content = $this->client->getResponse()->getContent();
        $this->assertContains('<table class="table table-sm table-striped table-bordered">', $content);
        $this->assertContains('/p/vendor/packageOld/1.0/Foo.html', $content);
        $this->assertContains('/p/vendor/packageNew/1.0/Foo.html', $content);
    }

    /**
     * @test
     */
    public function editRenderTableWithRedirectEntries()
    {
        $this->logIn();
        $this->client->request('GET', '/redirect/1/edit');
        $content = $this->client->getResponse()->getContent();
        $this->assertContains('/p/vendor/packageOld/1.0/Foo.html', $content);
        $this->assertContains('/p/vendor/packageNew/1.0/Foo.html', $content);
    }

    /**
     * @test
     */
    public function updateRenderTableWithRedirectEntries()
    {
        TestDoubleBundle::addProphecy(BambooClient::class, $this->prophesize(BambooClient::class));
        $this->client = static::createClient();
        $this->logIn();

        $crawler = $this->client->request('GET', '/redirect/1/edit');
        $content = $this->client->getResponse()->getContent();
        $this->assertContains('/p/vendor/packageOld/1.0/Foo.html', $content);
        $this->assertContains('/p/vendor/packageNew/1.0/Foo.html', $content);
        $this->assertContains('301', $content);


        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        $bambooClientProphecy->post(Argument::cetera())->willReturn(new Response());
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);

        $form = $crawler->selectButton('redirectformaction')->form([
            'docs_server_redirect' => [
                'source' => '/p/vendor/packageOld/1.0/Bar.html',
                'target' => '/p/vendor/packageNew/1.0/Bar.html',
                'statusCode' => 301
            ],
        ]);
        $this->client->submit($form);
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());

        TestDoubleBundle::addProphecy(BambooClient::class, $this->prophesize(BambooClient::class));
        $this->client = static::createClient();
        $this->logIn();

        $this->client->request('GET', '/redirect/1/edit');
        $content = $this->client->getResponse()->getContent();
        $this->assertContains('/p/vendor/packageOld/1.0/Bar.html', $content);
        $this->assertContains('/p/vendor/packageNew/1.0/Bar.html', $content);
        $this->assertContains('301', $content);
    }

    /**
     * @test
     */
    public function newRenderTableWithRedirectEntries()
    {
        TestDoubleBundle::addProphecy(BambooClient::class, $this->prophesize(BambooClient::class));
        $this->client = static::createClient();
        $this->logIn();

        $crawler = $this->client->request('GET', '/redirect/new');
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        $bambooClientProphecy->post(Argument::cetera())->willReturn(new Response());
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);

        $form = $crawler->selectButton('redirectformaction')->form([
            'docs_server_redirect' => [
                'source' => '/p/vendor/packageOld/4.0/Bar.html',
                'target' => '/p/vendor/packageNew/4.0/Bar.html',
                'statusCode' => 303
            ],
        ]);
        $this->client->submit($form);
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());

        TestDoubleBundle::addProphecy(BambooClient::class, $this->prophesize(BambooClient::class));
        $this->client = static::createClient();
        $this->logIn();

        $this->client->request('GET', '/redirect/');
        $content = $this->client->getResponse()->getContent();
        $this->assertContains('/p/vendor/packageOld/4.0/Bar.html', $content);
        $this->assertContains('/p/vendor/packageNew/4.0/Bar.html', $content);
    }

    /**
     * @test
     */
    public function deleteRenderTableWithRedirectEntries()
    {
        TestDoubleBundle::addProphecy(BambooClient::class, $this->prophesize(BambooClient::class));
        $this->client = static::createClient();
        $this->logIn();

        $this->client->request('GET', '/redirect/');
        $content = $this->client->getResponse()->getContent();
        $this->assertContains('/p/vendor/packageOld/1.0/Foo.html', $content);
        $this->assertContains('/p/vendor/packageNew/1.0/Foo.html', $content);

        $crawler = $this->client->request('GET', '/redirect/1/edit');
        $bambooClientProphecy = $this->prophesize(BambooClient::class);
        $bambooClientProphecy->post(Argument::cetera())->willReturn(new Response());
        TestDoubleBundle::addProphecy(BambooClient::class, $bambooClientProphecy);

        $form = $crawler->selectButton('redirectformdelete')->form();
        $this->client->submit($form);
        $this->assertEquals(302, $this->client->getResponse()->getStatusCode());

        TestDoubleBundle::addProphecy(BambooClient::class, $this->prophesize(BambooClient::class));
        $this->client = static::createClient();
        $this->logIn();

        $this->client->request('GET', '/redirect/');

        $content = $this->client->getResponse()->getContent();
        $this->assertNotContains('/p/vendor/packageOld/1.0/Foo.html', $content);
        $this->assertNotContains('/p/vendor/packageNew/1.0/Foo.html', $content);
        $this->assertContains('no records found', $content);
    }

    private function logIn()
    {
        $session = $this->client->getContainer()->get('session');

        $firewallName = 'main';
        // if you don't define multiple connected firewalls, the context defaults to the firewall name
        // See https://symfony.com/doc/current/reference/configuration/security.html#firewall-context
        $firewallContext = 'main';

        // you may need to use a different token class depending on your application.
        // for example, when using Guard authentication you must instantiate PostAuthenticationGuardToken
        $token = new UsernamePasswordToken('admin', null, $firewallName, ['ROLE_DOCUMENTATION_MAINTAINER']);
        $session->set('_security_'.$firewallContext, serialize($token));
        $session->save();

        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }
}
