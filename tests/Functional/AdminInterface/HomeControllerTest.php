<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Functional\AdminInterface;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    /**
     * @test
     */
    public function indexPageIsRendered()
    {
        $client = static::createClient();
        $client->request('GET', '/admin');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertRegExp('/About Intercept/', $client->getResponse()->getContent());
    }
}
