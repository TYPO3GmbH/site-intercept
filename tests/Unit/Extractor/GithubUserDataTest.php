<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Unit\Extractor;

use App\Exception\DoNotCareException;
use App\Extractor\GithubUserData;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class GithubUserDataTest extends TestCase
{
    public function testConstructorHandlesUsername(): void
    {
        $response = new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'name' => 'Lolli Foo',
            'email' => 'lolli@example.com',
        ], JSON_THROW_ON_ERROR));
        $subject = new GithubUserData($response);

        self::assertSame('Lolli Foo', $subject->user);
        self::assertSame('lolli@example.com', $subject->email);
    }

    public function testConstructorHandlesLoginname(): void
    {
        $response = new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'name' => 'lolli42',
            'email' => 'lolli@example.com',
        ], JSON_THROW_ON_ERROR));
        $subject = new GithubUserData($response);

        self::assertSame('lolli42', $subject->user);
        self::assertSame('lolli@example.com', $subject->email);
    }

    public function testConstructorThrowsWithEmptyName(): void
    {
        $this->expectException(DoNotCareException::class);

        $response = new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'name' => '',
            'email' => 'lolli@example.com',
        ], JSON_THROW_ON_ERROR));
        new GithubUserData($response);
    }
}
