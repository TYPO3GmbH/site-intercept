<?php
declare(strict_types = 1);
namespace App\Tests\Unit\Extractor;

use App\Extractor\BambooBuildStatus;
use App\Extractor\BambooBuildTriggered;
use PHPUnit\Framework\TestCase;

class BambooBuildTriggeredTest extends TestCase
{
    /**
     * @test
     */
    public function constructorExtractsValues()
    {
        $payload = json_encode([
            'buildResultKey' => 'CORE-GTC87',
        ]);
        $subject = new BambooBuildTriggered($payload);
        $this->assertSame('CORE-GTC87', $subject->buildResultKey);
    }
}
