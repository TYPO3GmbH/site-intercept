<?php
declare(strict_types = 1);
namespace App\Tests\Unit\Extractor;

use App\Extractor\PushEvent;
use App\Service\WebHookService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class WebHookServiceTest extends TestCase
{
    /**
     * @var WebHookService
     */
    private $subject;

    public function setUp()
    {
        $this->subject = new WebHookService();
    }

    public function createPushEventDataProvider(): array
    {
        return [
            'Payload_Bitbucket_Cloud_Event_Push' => [
                new Request([], [], [], [], [], ['HTTP_X-Event-Key' => 'repo:push'], file_get_contents(__DIR__ . '/Fixtures/Payload_Bitbucket_Cloud_Event_Push.json')),
                new PushEvent('https://bitbucket.org/DanielSiepmann/contacts', 'documentation-draft', 'https://bitbucket.org/DanielSiepmann/contacts/raw/documentation-draft/composer.json')
            ],
            'Payload_Bitbucket_Server_Event_Refs_Changed' => [
                new Request([], [], [], [], [], ['HTTP_X-Event-Key' => 'repo:refs_changed'], file_get_contents(__DIR__ . '/Fixtures/Payload_Bitbucket_Server_Event_Refs_Changed.json')),
                new PushEvent('https://bitbucket.typo3.com/scm/ext/querybuilder.git', 'documentation-draft', 'https://bitbucket.typo3.com/projects/EXT/repos/querybuilder/raw/composer.json?at=refs%2Fheads%2Fdocumentation-draft')
            ],
            'Payload_Bitbucket_Server_Event_Push' => [
                new Request([], [], [], [], [], ['HTTP_X-Event-Key' => 'repo:refs_changed'], file_get_contents(__DIR__ . '/Fixtures/Payload_Bitbucket_Server_Event_Push.json')),
                new PushEvent('https://bitbucket.typo3.com/scm/ext/querybuilder.git', 'documentation-draft', 'https://bitbucket.typo3.com/projects/EXT/repos/querybuilder/raw/composer.json?at=refs%2Fheads%2Fdocumentation-draft')
            ],
            'Payload_GitHub_Event_Push_Branch' => [
                new Request([], [], [], [], [], ['HTTP_X-GitHub-Event' => 'push'], file_get_contents(__DIR__ . '/Fixtures/Payload_GitHub_Event_Push_Branch.json')),
                new PushEvent('https://github.com/Codertocat/Hello-World.git', 'master', 'https://raw.githubusercontent.com/Codertocat/Hello-World/master/composer.json')
            ],
            'Payload_GitHub_Event_Push_Tag' => [
                new Request([], [], [], [], [], ['HTTP_X-GitHub-Event' => 'push'], file_get_contents(__DIR__ . '/Fixtures/Payload_GitHub_Event_Push_Tag.json')),
                new PushEvent('https://github.com/Codertocat/Hello-World.git', 'simple-tag', 'https://raw.githubusercontent.com/Codertocat/Hello-World/simple-tag/composer.json')
            ],
            'Payload_GitHub_Event_Release' => [
                new Request([], [], [], [], [], ['HTTP_X-GitHub-Event' => 'release'], file_get_contents(__DIR__ . '/Fixtures/Payload_GitHub_Event_Release.json')),
                new PushEvent('https://github.com/Codertocat/Hello-World.git', '0.0.1', 'https://raw.githubusercontent.com/Codertocat/Hello-World/0.0.1/composer.json')
            ],
            'Payload_Gitlab_Event_Push_Branch' => [
                new Request([], [], [], [], [], ['HTTP_X-Gitlab-Event' => 'Push Hook'], file_get_contents(__DIR__ . '/Fixtures/Payload_Gitlab_Event_Push_Branch.json')),
                new PushEvent('http://example.com/mike/diaspora.git', 'master', 'http://example.com/mike/diaspora/raw/master/composer.json')
            ],
            'Payload_Gitlab_Event_Push_Tag' => [
                new Request([], [], [], [], [], ['HTTP_X-Gitlab-Event' => 'Tag Push Hook'], file_get_contents(__DIR__ . '/Fixtures/Payload_Gitlab_Event_Push_Tag.json')),
                new PushEvent('http://example.com/jsmith/example.git', 'v1.0.0', 'http://example.com/jsmith/example/raw/v1.0.0/composer.json')
            ],
        ];
    }

    /**
     * @test
     * @dataProvider createPushEventDataProvider
     * @param Request $request
     * @param PushEvent $pushEvent
     */
    public function createPushEventReturnsValidPushEventObject(Request $request, PushEvent $pushEvent): void
    {
        $createdPushEvent = $this->subject->createPushEvent($request);
        $this->assertSame($pushEvent->getVersionString(), $createdPushEvent->getVersionString());
        $this->assertSame($pushEvent->getRepositoryUrl(), $createdPushEvent->getRepositoryUrl());
        $this->assertSame($pushEvent->getUrlToComposerFile(), $createdPushEvent->getUrlToComposerFile());
    }
}
