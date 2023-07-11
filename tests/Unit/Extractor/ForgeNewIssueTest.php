<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Unit\Extractor;

use App\Extractor\ForgeNewIssue;
use PHPUnit\Framework\TestCase;

class ForgeNewIssueTest extends TestCase
{
    public function testConstructorExtractsValues(): void
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0"?><root><id>42</id></root>');
        $subject = new ForgeNewIssue($xml);
        self::assertSame(42, $subject->id);
    }

    public function testConstructorThrowsIfIdWasNotFound(): void
    {
        $this->expectException(\RuntimeException::class);
        new ForgeNewIssue(new \SimpleXMLElement('<empty></empty>'));
    }
}
