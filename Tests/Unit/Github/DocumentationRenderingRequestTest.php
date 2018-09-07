<?php

namespace Codappix\Tests\Unit\Github;

/*
 * Copyright (C) 2018 Daniel Siepmann <daniel.siepmann@typo3.org>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301, USA.
 */

use PHPUnit\Framework\TestCase;
use T3G\Intercept\Github\DocumentationRenderingRequest;

class DocumentationRenderingRequestTest extends TestCase
{
    /**
     * @test
     * @dataProvider possibleVersionNumbers
     */
    public function expectedVersionNumberIsReturnedForInput(array $input, string $expectedVersionNumber)
    {
        $subject = new DocumentationRenderingRequest(json_encode($input));
        $this->assertSame($expectedVersionNumber, $subject->getVersionNumber());
    }

    public function possibleVersionNumbers(): array
    {
        return [
            'Latest Branch' => [
                'input' => [
                    'ref' => 'refs/heads/latest',
                    'repository' => [
                        'clone_url' => 'https://github.com/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript.git',
                    ],
                ],
                'latest',
            ],
            'Draft Branch' => [
                'input' => [
                    'ref' => 'refs/heads/draft',
                    'repository' => [
                        'clone_url' => 'https://github.com/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript.git',
                    ],
                ],
                'draft',
            ],
            'Release 9.4.2' => [
                'input' => [
                    'ref' => 'refs/tags/9.4.2',
                    'repository' => [
                        'clone_url' => 'https://github.com/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript.git',
                    ],
                ],
                '9.4',
            ],
        ];
    }
}
