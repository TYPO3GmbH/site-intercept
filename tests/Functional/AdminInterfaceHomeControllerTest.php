<?php
declare(strict_types = 1);
namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminInterfaceHomeControllerTest extends WebTestCase
{
    /**
     * @test
     */
    public function indexPageIsRendered()
    {
        $client = static::createClient();
        $client->request('GET', '/admin');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertRegExp('/Intercept web admin interface/', $client->getResponse()->getContent());
    }
}
