<?php
declare(strict_types = 1);
namespace App\Tests\Unit\Client;

use App\Client\GerritClient;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class GerritClientTest extends TestCase
{
    /**
     * @test
     */
    public function isInstanceOfGuzzleClient()
    {
        $this->assertInstanceOf(Client::class, new GerritClient());
    }
}
