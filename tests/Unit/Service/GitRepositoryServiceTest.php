<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Unit\Service;

use App\Service\GitRepositoryService;
use PHPUnit\Framework\TestCase;

class GitRepositoryServiceTest extends TestCase
{
    private GitRepositoryService $subject;

    public function setUp(): void
    {
        $this->subject = new GitRepositoryService();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('payloadDataProvider')]
    public function testComposerJsonUrlIsResolvedByPayload(string $expectedUrl, \stdClass $payload, string $repoService): void
    {
        $composerJsonUrl = $this->subject->resolvePublicComposerJsonUrlByPayload($payload, $repoService);

        $this->assertSame($expectedUrl, $composerJsonUrl, 'Resolved composer json url did not match.');
    }

    public static function payloadDataProvider(): \Iterator
    {
        yield 'Bitbucket (cloud) push' => [
            'https://bitbucket.org/DanielSiepmann/contacts/raw/documentation-draft/composer.json',
            self::getPayloadFromFixture('Payload_Bitbucket_Cloud_Event_Push.json'),
            GitRepositoryService::SERVICE_BITBUCKET_CLOUD,
        ];
        yield 'Bitbucket (server) push' => [
            'https://bitbucket.typo3.com/projects/EXT/repos/querybuilder/raw/composer.json?at='
                . urlencode('refs/heads/documentation-draft'),
            self::getPayloadFromFixture('Payload_Bitbucket_Server_Event_Push.json'),
            GitRepositoryService::SERVICE_BITBUCKET_SERVER,
        ];
        yield 'Bitbucket (server) push with tag' => [
            'https://bitbucket.typo3.com/projects/EXT/repos/querybuilder/raw/composer.json?at='
                . urlencode('refs/tags/v1.1.1'),
            self::getPayloadFromFixture('Payload_Bitbucket_Server_Event_Push_Tag.json'),
            GitRepositoryService::SERVICE_BITBUCKET_SERVER,
        ];
        yield 'GitHub (cloud) push branch' => [
            'https://raw.githubusercontent.com/Codertocat/Hello-World/main/composer.json',
            self::getPayloadFromFixture('Payload_GitHub_Event_Push_Branch.json'),
            GitRepositoryService::SERVICE_GITHUB,
        ];
        yield 'GitHub (cloud) push tag' => [
            'https://raw.githubusercontent.com/Codertocat/Hello-World/simple-tag/composer.json',
            self::getPayloadFromFixture('Payload_GitHub_Event_Push_Tag.json'),
            GitRepositoryService::SERVICE_GITHUB,
        ];
        yield 'GitLab (cloud) push branch' => [
            'http://example.com/mike/diaspora/raw/main/composer.json',
            self::getPayloadFromFixture('Payload_Gitlab_Event_Push_Branch.json'),
            GitRepositoryService::SERVICE_GITLAB,
        ];
        yield 'GitLab (cloud) push tag' => [
            'http://example.com/jsmith/example/raw/v1.0.0/composer.json',
            self::getPayloadFromFixture('Payload_Gitlab_Event_Push_Tag.json'),
            GitRepositoryService::SERVICE_GITLAB,
        ];
    }

    public static function filterAllowedBranchesDataProvider(): \Iterator
    {
        yield 'one version, main only' => [
            ['main'], // input
            ['main' => 'main'], // expected
        ];
        yield 'two versions, main and a semver one' => [
            ['main', '1.0.1'],
            ['main' => 'main', '1.0.1' => '1.0'],
        ];
        yield 'multiple versions, distinct targets' => [
            ['main', '1.0.4', '2.8.7'],
            ['main' => 'main', '1.0.4' => '1.0', '2.8.7' => '2.8'],
        ];
        yield 'one version, semver with leading v' => [
            ['v1.0.1'],
            ['v1.0.1' => '1.0'],
        ];
        yield 'main not on top will put main on top' => [
            ['v1.0.1', 'v2.5.8', 'main', '5.7.6'],
            ['main' => 'main', 'v1.0.1' => '1.0', 'v2.5.8' => '2.5', '5.7.6' => '5.7'],
        ];
        yield 'multiple invalid versions lead to empty result' => [
            ['v1.1', 'v1', '1'],
            [],
        ];
        yield 'mixed versions with leading v and without' => [
            ['v1.0.5', 'v2.6.0', '4.7.99'],
            ['v1.0.5' => '1.0', 'v2.6.0' => '2.6', '4.7.99' => '4.7'],
        ];
        yield 'various invalid versions, and one valid one' => [
            ['we-dont-want-this-branch', 'we-dont-want-this-branch-either', 'v1.6.3'],
            ['v1.6.3' => '1.6'],
        ];
        yield 'version suffixes like -beta are ignored' => [
            ['v1.5.6-beta', 'v1.6.8'],
            ['v1.6.8' => '1.6'],
        ];
        yield 'latest patch level is found if "last" one is in front' => [
            ['1.2.4', '1.2.2', '1.2.3'],
            ['1.2.4' => '1.2'],
        ];
        yield 'latest patch level is found if "last" one is in the middle' => [
            ['1.2.2', '1.2.4', '1.2.3'],
            ['1.2.4' => '1.2'],
        ];
        yield 'latest patch level is found if "last" one is at the end' => [
            ['1.2.2', '1.2.3', '1.2.4'],
            ['1.2.4' => '1.2'],
        ];
        yield 'latest patch level is found if "last" one is in front with leading v' => [
            ['v1.2.4', 'v1.2.2', 'v1.2.3'],
            ['v1.2.4' => '1.2'],
        ];
        yield 'latest patch level is found if "last" one is in the middle with leading v' => [
            ['v1.2.2', 'v1.2.4', 'v1.2.3'],
            ['v1.2.4' => '1.2'],
        ];
        yield 'latest patch level is found if "last" one is at the end with leading v' => [
            ['v1.2.2', 'v1.2.3', 'v1.2.4'],
            ['v1.2.4' => '1.2'],
        ];
        yield 'latest patch level is found if "last" one is in front and v is mixed in with last having v' => [
            ['v1.2.4', '1.2.2', 'v1.2.3'],
            ['v1.2.4' => '1.2'],
        ];
        yield 'latest patch level is found if "last" one is in the middle and v is mixed in with last having v' => [
            ['1.2.2', 'v1.2.4', 'v1.2.3'],
            ['v1.2.4' => '1.2'],
        ];
        yield 'latest patch level is found if "last" one is at the end and v is mixed in with last having v' => [
            ['1.2.2', 'v1.2.3', 'v1.2.4'],
            ['v1.2.4' => '1.2'],
        ];
        yield 'latest patch level is found if "last" one is in front and v is mixed in with last not having v' => [
            ['1.2.4', '1.2.2', 'v1.2.3'],
            ['1.2.4' => '1.2'],
        ];
        yield 'latest patch level is found if "last" one is in the middle and v is mixed in with last not having v' => [
            ['1.2.2', '1.2.4', 'v1.2.3'],
            ['1.2.4' => '1.2'],
        ];
        yield 'latest patch level is found if "last" one is at the end and v is mixed in with last not having v' => [
            ['1.2.2', 'v1.2.3', '1.2.4'],
            ['1.2.4' => '1.2'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('filterAllowedBranchesDataProvider')]
    public function testFilterAllowedBranchesReturnsOnlyValidBranchNames(array $values, array $expected): void
    {
        $data = $this->subject->filterAllowedBranches($values);

        $this->assertEquals($expected, $data);
    }

    private static function getPayloadFromFixture(string $fixtureFile): \stdClass
    {
        $file = __DIR__ . '/Fixtures/' . $fixtureFile;

        return json_decode(file_get_contents($file), null, 512, JSON_THROW_ON_ERROR);
    }
}
