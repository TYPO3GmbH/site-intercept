<?php
declare(strict_types = 1);
namespace App\Tests\Unit\Exception;

use App\Exception\DoNotCareException;
use PHPUnit\Framework\TestCase;

class DoNotCareExceptionTest extends TestCase
{
    /**
     * @test
     */
    public function isInstanceOfException()
    {
        $this->assertInstanceOf(\Exception::class, new DoNotCareException());
    }
}
