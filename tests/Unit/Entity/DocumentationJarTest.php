<?php

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
    /**
     * @return array
     */
    public function repositoryUrlDataProvider(): array
    {
        return [
            ['https://github.com/mautic/mautic-typo3.git', 1],
            ['https://github.com/mautic/mautic-typo3', 0],
            ['http://github.com/mautic/mautic-typo3.git', 0],
            ['https://bitbucket.org/vendor/package.git', 1],
            ['https://gitlab.com/vendor/package.git', 1],
        ];
    }

    /**
     * @test
     * @dataProvider repositoryUrlDataProvider
     * @param string $url
     * @param int $expected
     */
    public function testRepositoryUrlRegex(string $url, int $expected): void
    {
        $result = preg_match(DocumentationJar::VALID_REPOSITORY_URL_REGEX, $url);

        $this->assertEquals($expected, $result);
    }
}
