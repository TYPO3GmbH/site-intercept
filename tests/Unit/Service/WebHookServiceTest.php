<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Unit\Service;

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

    public function setUp(): void
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
                new PushEvent('https://github.com/Codertocat/Hello-World.git', 'main', 'https://raw.githubusercontent.com/Codertocat/Hello-World/main/composer.json')
            ],
            'Payload_GitHub_Event_Push_Tag' => [
                new Request([], [], [], [], [], ['HTTP_X-GitHub-Event' => 'push'], file_get_contents(__DIR__ . '/Fixtures/Payload_GitHub_Event_Push_Tag.json')),
                new PushEvent('https://github.com/Codertocat/Hello-World.git', 'simple-tag', 'https://raw.githubusercontent.com/Codertocat/Hello-World/simple-tag/composer.json')
            ],
            'Payload_Gitlab_Event_Push_Branch' => [
                new Request([], [], [], [], [], ['HTTP_X-Gitlab-Event' => 'Push Hook'], file_get_contents(__DIR__ . '/Fixtures/Payload_Gitlab_Event_Push_Branch.json')),
                new PushEvent('http://example.com/mike/diaspora.git', 'main', 'http://example.com/mike/diaspora/raw/main/composer.json')
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
        $this->assertSame($pushEvent->getVersionString(), $createdPushEvent[0]->getVersionString());
        $this->assertSame($pushEvent->getRepositoryUrl(), $createdPushEvent[0]->getRepositoryUrl());
        $this->assertSame($pushEvent->getUrlToComposerFile(), $createdPushEvent[0]->getUrlToComposerFile());
    }

    public function createPushEventsDataProvider(): array
    {
        return [
            'Payload_Bitbucket_Cloud_Event_Push_Multiple' => [
                new Request([], [], [], [], [], ['HTTP_X-Event-Key' => 'repo:push'], file_get_contents(__DIR__ . '/Fixtures/Payload_Bitbucket_Cloud_Event_Push_Multiple.json')),
                [
                    new PushEvent('https://bitbucket.org/pathfindermediagroup/eso-export-addon', 'main', 'https://bitbucket.org/pathfindermediagroup/eso-export-addon/raw/main/composer.json'),
                    new PushEvent('https://bitbucket.org/pathfindermediagroup/eso-export-addon', 'test', 'https://bitbucket.org/pathfindermediagroup/eso-export-addon/raw/test/composer.json'),
                ]
            ],
            'Payload_Bitbucket_Server_Event_Refs_Changed_Multiple' => [
                new Request([], [], [], [], [], ['HTTP_X-Event-Key' => 'repo:refs_changed'], file_get_contents(__DIR__ . '/Fixtures/Payload_Bitbucket_Server_Event_Refs_Changed.json')),
                [
                    new PushEvent('https://bitbucket.typo3.com/scm/ext/querybuilder.git', 'documentation-draft', 'https://bitbucket.typo3.com/projects/EXT/repos/querybuilder/raw/composer.json?at=refs%2Fheads%2Fdocumentation-draft'),
                    new PushEvent('https://bitbucket.typo3.com/scm/ext/querybuilder.git', 'some-other-branch', 'https://bitbucket.typo3.com/projects/EXT/repos/querybuilder/raw/composer.json?at=refs%2Fheads%2Fsome-other-branch'),
                ]
            ],
            'Payload_Bitbucket_Server_Event_Push_Multiple' => [
                new Request([], [], [], [], [], ['HTTP_X-Event-Key' => 'repo:refs_changed'], file_get_contents(__DIR__ . '/Fixtures/Payload_Bitbucket_Server_Event_Push.json')),
                [
                    new PushEvent('https://bitbucket.typo3.com/scm/ext/querybuilder.git', 'documentation-draft', 'https://bitbucket.typo3.com/projects/EXT/repos/querybuilder/raw/composer.json?at=refs%2Fheads%2Fdocumentation-draft'),
                    new PushEvent('https://bitbucket.typo3.com/scm/ext/querybuilder.git', 'some-other-branch', 'https://bitbucket.typo3.com/projects/EXT/repos/querybuilder/raw/composer.json?at=refs%2Fheads%2Fsome-other-branch'),
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider createPushEventsDataProvider
     * @param Request $request
     * @param array $pushEvents
     */
    public function createPushEventsReturnsValidPushEventObject(Request $request, array $pushEvents): void
    {
        $createdPushEvent = $this->subject->createPushEvent($request);
        $this->assertSame($pushEvents[0]->getVersionString(), $createdPushEvent[0]->getVersionString());
        $this->assertSame($pushEvents[0]->getRepositoryUrl(), $createdPushEvent[0]->getRepositoryUrl());
        $this->assertSame($pushEvents[0]->getUrlToComposerFile(), $createdPushEvent[0]->getUrlToComposerFile());

        $this->assertSame($pushEvents[1]->getVersionString(), $createdPushEvent[1]->getVersionString());
        $this->assertSame($pushEvents[1]->getRepositoryUrl(), $createdPushEvent[1]->getRepositoryUrl());
        $this->assertSame($pushEvents[1]->getUrlToComposerFile(), $createdPushEvent[1]->getUrlToComposerFile());
    }

    public function createPushEventsWithDocFiles(): array
    {
        return [
            'File added' => [
                'request' => new Request([], [], [], [], [], ['HTTP_X-GitHub-Event' => 'push'], file_get_contents(__DIR__ . '/Fixtures/Payload_GitHub_Event_Push_Added_Rst.json')),
            ],
            'File modified' => [
                'request' => new Request([], [], [], [], [], ['HTTP_X-GitHub-Event' => 'push'], file_get_contents(__DIR__ . '/Fixtures/Payload_GitHub_Event_Push_Modified_Rst.json')),
            ],
            'File removed' => [
                'request' => new Request([], [], [], [], [], ['HTTP_X-GitHub-Event' => 'push'], file_get_contents(__DIR__ . '/Fixtures/Payload_GitHub_Event_Push_Removed_Rst.json')),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider createPushEventsWithDocFiles
     */
    public function doesNotTriggersExceptionWhenDocFileWasTouched(Request $request)
    {
        $createdPushEvent = $this->subject->createPushEvent($request);
        // Plain assertion, we ensure that no exception is thrown
        $this->assertTrue(true);
    }
}
