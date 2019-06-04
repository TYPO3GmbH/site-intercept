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
                'payload' => $this->getPayloadFromFixture('Payload_Bitbucket_Event_Push.json'),
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

    private function getPayloadFromFixture(string $fixtureFile): \stdClass
    {
        $file = __DIR__ . '/Fixtures/' . $fixtureFile;
        return json_decode(file_get_contents($file));
    }
}
