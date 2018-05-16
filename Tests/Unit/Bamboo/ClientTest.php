<?php
declare(strict_types = 1);
namespace T3G\Intercept\Tests\Unit\Bamboo;

use PHPUnit\Framework\TestCase;
use T3G\Intercept\Bamboo\Client;

class ClientTest extends TestCase
{
    /**
     * @test
     * @return void
     */
    public function triggerNewCoreBuildThrowsExceptionIfBranchToProjectMappingLookupFails()
    {
        $client = new Client();
        $client->setBranchToProjectKey(['klaus' => 'fritz']);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1472210110);
        $client->triggerNewCoreBuild('foo', 3, 'master');
    }
}
