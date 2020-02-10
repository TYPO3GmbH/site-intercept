<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Unit\Extractor;

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
