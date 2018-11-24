<?php
declare(strict_types = 1);
namespace App\Tests\Unit\Client;

use App\Client\BambooClient;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class BambooClientTest extends TestCase
{
    /**
     * @test
     */
    public function isInstanceOfGuzzleClient()
    {
        $this->assertInstanceOf(Client::class, new BambooClient());
    }
}
