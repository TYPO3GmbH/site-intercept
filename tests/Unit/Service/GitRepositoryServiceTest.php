<?php
declare(strict_types = 1);
namespace App\Tests\Unit\Service;

use App\Service\GitRepositoryService;
use PHPUnit\Framework\TestCase;

class GitRepositoryServiceTest extends TestCase
{
    /**
     * @var GitRepositoryService
     */
    private $subject;

    public function setUp()
    {
        $this->subject = new GitRepositoryService();
    }

    /**
     * @test
     * @dataProvider payloadDataProvider
     */
    public function composerJsonUrlIsResolvedByPayload(string $expectedUrl, \stdClass $payload, string $repoService, string $eventType = null)
    {
        $composerJsonUrl = $this->subject->resolvePublicComposerJsonUrlByPayload($payload, $repoService, $eventType);

        $this->assertSame($expectedUrl, $composerJsonUrl, 'Resolved composer json url did not match.');
    }

    public function payloadDataProvider(): array
    {
        return [
            'Bitbucket (cloud) push' => [
                'expectedUrl' => 'https://bitbucket.org/DanielSiepmann/contacts/raw/documentation-draft/composer.json',
                'payload' => $this->getPayloadFromFixture('Payload_Bitbucket_Cloud_Event_Push.json'),
                'repoService' => GitRepositoryService::SERVICE_BITBUCKET_CLOUD,
                'eventType' => null,
            ],
            'Bitbucket (server) push' => [
                'expectedUrl' => 'https://bitbucket.typo3.com/projects/EXT/repos/querybuilder/raw/composer.json?at='
                    . urlencode('refs/heads/documentation-draft'),
                'payload' => $this->getPayloadFromFixture('Payload_Bitbucket_Server_Event_Push.json'),
                'repoService' => GitRepositoryService::SERVICE_BITBUCKET_SERVER,
                'eventType' => null,
            ],
            'Bitbucket (server) push with tag' => [
                'expectedUrl' => 'https://bitbucket.typo3.com/projects/EXT/repos/querybuilder/raw/composer.json?at='
                    . urlencode('refs/tags/v1.1.1'),
                'payload' => $this->getPayloadFromFixture('Payload_Bitbucket_Server_Event_Push_Tag.json'),
                'repoService' => GitRepositoryService::SERVICE_BITBUCKET_SERVER,
                'eventType' => null,
            ],
            'GitHub (cloud) push branch' => [
                'expectedUrl' => 'https://raw.githubusercontent.com/Codertocat/Hello-World/master/composer.json',
                'payload' => $this->getPayloadFromFixture('Payload_GitHub_Event_Push_Branch.json'),
                'repoService' => GitRepositoryService::SERVICE_GITHUB,
                'eventType' => 'someevent',
            ],
            'GitHub (cloud) push tag' => [
                'expectedUrl' => 'https://raw.githubusercontent.com/Codertocat/Hello-World/simple-tag/composer.json',
                'payload' => $this->getPayloadFromFixture('Payload_GitHub_Event_Push_Tag.json'),
                'repoService' => GitRepositoryService::SERVICE_GITHUB,
                'eventType' => 'someevent',
            ],
            'GitHub (cloud) push release' => [
                'expectedUrl' => 'https://raw.githubusercontent.com/Codertocat/Hello-World/0.0.1/composer.json',
                'payload' => $this->getPayloadFromFixture('Payload_GitHub_Event_Release.json'),
                'repoService' => GitRepositoryService::SERVICE_GITHUB,
                'eventType' => 'release',
            ],
            'GitLab (cloud) push branch' => [
                'expectedUrl' => 'http://example.com/mike/diaspora/raw/master/composer.json',
                'payload' => $this->getPayloadFromFixture('Payload_Gitlab_Event_Push_Branch.json'),
                'repoService' => GitRepositoryService::SERVICE_GITLAB,
                'eventType' => null,
            ],
            'GitLab (cloud) push tag' => [
                'expectedUrl' => 'http://example.com/jsmith/example/raw/v1.0.0/composer.json',
                'payload' => $this->getPayloadFromFixture('Payload_Gitlab_Event_Push_Tag.json'),
                'repoService' => GitRepositoryService::SERVICE_GITLAB,
                'eventType' => null,
            ],
        ];
    }

    public function filterAllowedBranchesDataProvider(): array
    {
        return [
            'one version, master only' => [
                ['master'], // input
                ['master' => 'master'] // expected
            ],
            'two versions, master and a semver one' => [
                ['master', '1.0.1'],
                ['master' => 'master', '1.0.1' => '1.0']
            ],
            'multiple versions, distinct targets' => [
                ['master', '1.0.4', '2.8.7'],
                ['master' => 'master', '1.0.4' => '1.0', '2.8.7' => '2.8']
            ],
            'one version, semver with leading v' => [
                ['v1.0.1'],
                ['v1.0.1' => '1.0']
            ],
            'master not on top will put master on top' => [
                ['v1.0.1', 'v2.5.8', 'master', '5.7.6'],
                ['master' => 'master', 'v1.0.1' => '1.0', 'v2.5.8' => '2.5', '5.7.6' => '5.7']
            ],
            'multiple invalid versions lead to empty result' => [
                ['v1.1', 'v1', '1'],
                []
            ],
            'mixed versions with leading v and without' => [
                ['v1.0.5', 'v2.6.0', '4.7.99'],
                ['v1.0.5' => '1.0', 'v2.6.0' => '2.6', '4.7.99' => '4.7']
            ],
            'various invalid versions, and one valid one' => [
                ['we-dont-want-this-branch', 'we-dont-want-this-branch-either', 'v1.6.3'],
                ['v1.6.3' => '1.6']
            ],
            'version suffixes like -beta are ignored' => [
                ['v1.5.6-beta', 'v1.6.8'],
                ['v1.6.8' => '1.6']
            ],
            'latest patch level is found if "last" one is in front' => [
                ['1.2.4', '1.2.2', '1.2.3'],
                ['1.2.4' => '1.2']
            ],
            'latest patch level is found if "last" one is in the middle' => [
                ['1.2.2', '1.2.4', '1.2.3'],
                ['1.2.4' => '1.2']
            ],
            'latest patch level is found if "last" one is at the end' => [
                ['1.2.2', '1.2.3', '1.2.4'],
                ['1.2.4' => '1.2']
            ],
            'latest patch level is found if "last" one is in front with leading v' => [
                ['v1.2.4', 'v1.2.2', 'v1.2.3'],
                ['v1.2.4' => '1.2']
            ],
            'latest patch level is found if "last" one is in the middle with leading v' => [
                ['v1.2.2', 'v1.2.4', 'v1.2.3'],
                ['v1.2.4' => '1.2']
            ],
            'latest patch level is found if "last" one is at the end with leading v' => [
                ['v1.2.2', 'v1.2.3', 'v1.2.4'],
                ['v1.2.4' => '1.2']
            ],
            'latest patch level is found if "last" one is in front and v is mixed in with last having v' => [
                ['v1.2.4', '1.2.2', 'v1.2.3'],
                ['v1.2.4' => '1.2']
            ],
            'latest patch level is found if "last" one is in the middle and v is mixed in with last having v' => [
                ['1.2.2', 'v1.2.4', 'v1.2.3'],
                ['v1.2.4' => '1.2']
            ],
            'latest patch level is found if "last" one is at the end and v is mixed in with last having v' => [
                ['1.2.2', 'v1.2.3', 'v1.2.4'],
                ['v1.2.4' => '1.2']
            ],
            'latest patch level is found if "last" one is in front and v is mixed in with last not having v' => [
                ['1.2.4', '1.2.2', 'v1.2.3'],
                ['1.2.4' => '1.2']
            ],
            'latest patch level is found if "last" one is in the middle and v is mixed in with last not having v' => [
                ['1.2.2', '1.2.4', 'v1.2.3'],
                ['1.2.4' => '1.2']
            ],
            'latest patch level is found if "last" one is at the end and v is mixed in with last not having v' => [
                ['1.2.2', 'v1.2.3', '1.2.4'],
                ['1.2.4' => '1.2']
            ],
        ];
    }

    /**
     * @test
     * @dataProvider filterAllowedBranchesDataProvider
     */
    public function filterAllowedBranchesReturnsOnlyValidBranchNames(array $values, array $expected): void
    {
        $data = $this->subject->filterAllowedBranches($values);

        $this->assertEquals($expected, $data);
    }

    private function getPayloadFromFixture(string $fixtureFile): \stdClass
    {
        $file = __DIR__ . '/Fixtures/' . $fixtureFile;
        return json_decode(file_get_contents($file));
    }
}
