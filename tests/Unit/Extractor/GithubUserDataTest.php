<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Unit\Extractor;

use App\Exception\DoNotCareException;
use App\Extractor\GithubUserData;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class GithubUserDataTest extends TestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;
    /**
     * @test
     */
    public function constructorHandlesUsername()
    {
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $payload = [
            'name' => 'Lolli Foo',
            'email' => 'lolli@example.com',
        ];
        $responseProphecy->getBody()->willReturn(json_encode($payload));
        $subject = new GithubUserData($responseProphecy->reveal());
        $this->assertSame('Lolli Foo', $subject->user);
        $this->assertSame('lolli@example.com', $subject->email);
    }

    /**
     * @test
     */
    public function constructorHandlesLoginname()
    {
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $payload = [
            'login' => 'lolli42',
            'email' => 'lolli@example.com',
        ];
        $responseProphecy->getBody()->willReturn(json_encode($payload));
        $subject = new GithubUserData($responseProphecy->reveal());
        $this->assertSame('lolli42', $subject->user);
        $this->assertSame('lolli@example.com', $subject->email);
    }

    /**
     * @test
     */
    public function constructorThrowsWithEmptyName()
    {
        $this->expectException(DoNotCareException::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $payload = [
            'name' => '',
            'email' => 'lolli@example.com',
        ];
        $responseProphecy->getBody()->willReturn(json_encode($payload));
        new GithubUserData($responseProphecy->reveal());
    }
}
