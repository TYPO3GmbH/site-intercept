<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Functional\AdminInterface;

use App\Tests\Functional\AbstractFunctionalWebTestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class HomeControllerTest extends AbstractFunctionalWebTestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function indexPageIsRendered()
    {
        $this->addRabbitManagementClientProphecy();
        $client = static::createClient();
        $client->request('GET', '/admin');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}
