<?php
declare(strict_types = 1);

namespace T3G\Intercept\Tests\Integration\Forge;

use Psr\Log\LoggerInterface;
use T3G\Intercept\Forge\Client;

class ClientTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @test
     * @return void
     */
    public function getIssueTest()
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $client = new Client($logger->reveal());
        $result = $client->createIssue('test title', 'test body');
        self::assertInternalType('int', $result->id);
    }
}
