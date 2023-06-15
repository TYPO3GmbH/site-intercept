<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Functional\Fixtures\JKU;

use Jose\Component\Core\JWKSet;
use Jose\Component\KeyManagement\JKUFactory;

class JKUFactoryStub extends JKUFactory
{
    public function loadFromUrl(string $url, array $header = []): JWKSet
    {
        return JWKSet::createFromJson(file_get_contents(__DIR__ . '/response.json'));
    }
}
