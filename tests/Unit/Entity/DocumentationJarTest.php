<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Unit\Entity;

use App\Entity\DocumentationJar;
use PHPUnit\Framework\TestCase;

class DocumentationJarTest extends TestCase
{
    public static function repositoryUrlDataProvider(): \Iterator
    {
        yield ['https://github.com/mautic/mautic-typo3.git', 1];
        yield ['https://github.com/mautic/mautic-typo3', 0];
        yield ['http://github.com/mautic/mautic-typo3.git', 0];
        yield ['https://bitbucket.org/vendor/package.git', 1];
        yield ['https://gitlab.com/vendor/package.git', 1];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('repositoryUrlDataProvider')]
    public function testRepositoryUrlRegex(string $url, int $expected): void
    {
        $result = preg_match(DocumentationJar::VALID_REPOSITORY_URL_REGEX, $url);

        $this->assertSame($expected, $result);
    }
}
