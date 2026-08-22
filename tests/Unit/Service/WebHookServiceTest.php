<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Unit\Service;

use App\Exception\DocsNoRstChangesException;
use App\Exception\GitBranchDeletedException;
use App\Exception\InvalidWebHookPayloadException;
use App\Exception\UnsupportedWebHookRequestException;
use App\Extractor\PushEvent;
use App\Service\WebHookService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class WebHookServiceTest extends TestCase
{
    private WebHookService $subject;

    public function setUp(): void
    {
        $this->subject = new WebHookService();
    }

    public static function createPushEventDataProvider(): \Iterator
    {
        yield 'Payload_Bitbucket_Cloud_Event_Push' => [
            new Request([], [], [], [], [], ['HTTP_X-Event-Key' => 'repo:push'], file_get_contents(__DIR__ . '/Fixtures/Payload_Bitbucket_Cloud_Event_Push.json')),
            new PushEvent('https://bitbucket.org/DanielSiepmann/contacts', 'documentation-draft', 'https://bitbucket.org/DanielSiepmann/contacts/raw/documentation-draft/composer.json', '{}'),
        ];
        yield 'Payload_Bitbucket_Server_Event_Refs_Changed' => [
            new Request([], [], [], [], [], ['HTTP_X-Event-Key' => 'repo:refs_changed'], file_get_contents(__DIR__ . '/Fixtures/Payload_Bitbucket_Server_Event_Refs_Changed.json')),
            new PushEvent('https://bitbucket.typo3.com/scm/ext/querybuilder.git', 'documentation-draft', 'https://bitbucket.typo3.com/projects/EXT/repos/querybuilder/raw/composer.json?at=refs%2Fheads%2Fdocumentation-draft', '{}'),
        ];
        yield 'Payload_Bitbucket_Server_Event_Push' => [
            new Request([], [], [], [], [], ['HTTP_X-Event-Key' => 'repo:refs_changed'], file_get_contents(__DIR__ . '/Fixtures/Payload_Bitbucket_Server_Event_Push.json')),
            new PushEvent('https://bitbucket.typo3.com/scm/ext/querybuilder.git', 'documentation-draft', 'https://bitbucket.typo3.com/projects/EXT/repos/querybuilder/raw/composer.json?at=refs%2Fheads%2Fdocumentation-draft', '{}'),
        ];
        yield 'Payload_GitHub_Event_Push_Branch' => [
            new Request([], [], [], [], [], ['HTTP_X-GitHub-Event' => 'push'], file_get_contents(__DIR__ . '/Fixtures/Payload_GitHub_Event_Push_Branch.json')),
            new PushEvent('https://github.com/Codertocat/Hello-World.git', 'main', 'https://raw.githubusercontent.com/Codertocat/Hello-World/main/composer.json', '{}'),
        ];
        yield 'Payload_GitHub_Event_Push_Tag' => [
            new Request([], [], [], [], [], ['HTTP_X-GitHub-Event' => 'push'], file_get_contents(__DIR__ . '/Fixtures/Payload_GitHub_Event_Push_Tag.json')),
            new PushEvent('https://github.com/Codertocat/Hello-World.git', 'simple-tag', 'https://raw.githubusercontent.com/Codertocat/Hello-World/simple-tag/composer.json', '{}'),
        ];
        yield 'Payload_Gitlab_Event_Push_Branch' => [
            new Request([], [], [], [], [], ['HTTP_X-Gitlab-Event' => 'Push Hook'], file_get_contents(__DIR__ . '/Fixtures/Payload_Gitlab_Event_Push_Branch.json')),
            new PushEvent('http://example.com/mike/diaspora.git', 'main', 'http://example.com/mike/diaspora/raw/main/composer.json', '{}'),
        ];
        yield 'Payload_Gitlab_Event_Push_Tag' => [
            new Request([], [], [], [], [], ['HTTP_X-Gitlab-Event' => 'Tag Push Hook'], file_get_contents(__DIR__ . '/Fixtures/Payload_Gitlab_Event_Push_Tag.json')),
            new PushEvent('http://example.com/jsmith/example.git', 'v1.0.0', 'http://example.com/jsmith/example/raw/v1.0.0/composer.json', '{}'),
        ];
    }

    #[DataProvider('createPushEventDataProvider')]
    public function dataCreatePushEventReturnsValidPushEventObject(Request $request, PushEvent $pushEvent): void
    {
        $createdPushEvent = $this->subject->createPushEvent($request);
        $this->assertSame($pushEvent->getVersionString(), $createdPushEvent[0]->getVersionString());
        $this->assertSame($pushEvent->getRepositoryUrl(), $createdPushEvent[0]->getRepositoryUrl());
        $this->assertSame($pushEvent->getUrlToComposerFile(), $createdPushEvent[0]->getUrlToComposerFile());
        $this->assertSame($pushEvent->getPayload(), $createdPushEvent[0]->getPayload());
    }

    public static function createPushEventsDataProvider(): \Iterator
    {
        yield 'Payload_Bitbucket_Cloud_Event_Push_Multiple' => [
            new Request([], [], [], [], [], ['HTTP_X-Event-Key' => 'repo:push'], file_get_contents(__DIR__ . '/Fixtures/Payload_Bitbucket_Cloud_Event_Push_Multiple.json')),
            [
                new PushEvent('https://bitbucket.org/pathfindermediagroup/eso-export-addon', 'main', 'https://bitbucket.org/pathfindermediagroup/eso-export-addon/raw/main/composer.json', '{}'),
                new PushEvent('https://bitbucket.org/pathfindermediagroup/eso-export-addon', 'test', 'https://bitbucket.org/pathfindermediagroup/eso-export-addon/raw/test/composer.json', '{}'),
            ],
        ];
        yield 'Payload_Bitbucket_Server_Event_Refs_Changed_Multiple' => [
            new Request([], [], [], [], [], ['HTTP_X-Event-Key' => 'repo:refs_changed'], file_get_contents(__DIR__ . '/Fixtures/Payload_Bitbucket_Server_Event_Refs_Changed.json')),
            [
                new PushEvent('https://bitbucket.typo3.com/scm/ext/querybuilder.git', 'documentation-draft', 'https://bitbucket.typo3.com/projects/EXT/repos/querybuilder/raw/composer.json?at=refs%2Fheads%2Fdocumentation-draft', '{}'),
                new PushEvent('https://bitbucket.typo3.com/scm/ext/querybuilder.git', 'some-other-branch', 'https://bitbucket.typo3.com/projects/EXT/repos/querybuilder/raw/composer.json?at=refs%2Fheads%2Fsome-other-branch', '{}'),
            ],
        ];
        yield 'Payload_Bitbucket_Server_Event_Push_Multiple' => [
            new Request([], [], [], [], [], ['HTTP_X-Event-Key' => 'repo:refs_changed'], file_get_contents(__DIR__ . '/Fixtures/Payload_Bitbucket_Server_Event_Push.json')),
            [
                new PushEvent('https://bitbucket.typo3.com/scm/ext/querybuilder.git', 'documentation-draft', 'https://bitbucket.typo3.com/projects/EXT/repos/querybuilder/raw/composer.json?at=refs%2Fheads%2Fdocumentation-draft', '{}'),
                new PushEvent('https://bitbucket.typo3.com/scm/ext/querybuilder.git', 'some-other-branch', 'https://bitbucket.typo3.com/projects/EXT/repos/querybuilder/raw/composer.json?at=refs%2Fheads%2Fsome-other-branch', '{}'),
            ],
        ];
    }

    #[DataProvider('createPushEventsDataProvider')]
    public function testCreatePushEventsReturnsValidPushEventObject(Request $request, array $pushEvents): void
    {
        $createdPushEvent = $this->subject->createPushEvent($request);
        $this->assertSame($pushEvents[0]->getVersionString(), $createdPushEvent[0]->getVersionString());
        $this->assertSame($pushEvents[0]->getRepositoryUrl(), $createdPushEvent[0]->getRepositoryUrl());
        $this->assertSame($pushEvents[0]->getUrlToComposerFile(), $createdPushEvent[0]->getUrlToComposerFile());

        $this->assertSame($pushEvents[1]->getVersionString(), $createdPushEvent[1]->getVersionString());
        $this->assertSame($pushEvents[1]->getRepositoryUrl(), $createdPushEvent[1]->getRepositoryUrl());
        $this->assertSame($pushEvents[1]->getUrlToComposerFile(), $createdPushEvent[1]->getUrlToComposerFile());
    }

    public static function createPushEventsWithDocFiles(): \Iterator
    {
        yield 'File added' => [
            new Request([], [], [], [], [], ['HTTP_X-GitHub-Event' => 'push'], file_get_contents(__DIR__ . '/Fixtures/Payload_GitHub_Event_Push_Added_Rst.json')),
        ];
        yield 'File modified' => [
            new Request([], [], [], [], [], ['HTTP_X-GitHub-Event' => 'push'], file_get_contents(__DIR__ . '/Fixtures/Payload_GitHub_Event_Push_Modified_Rst.json')),
        ];
        yield 'File removed' => [
            new Request([], [], [], [], [], ['HTTP_X-GitHub-Event' => 'push'], file_get_contents(__DIR__ . '/Fixtures/Payload_GitHub_Event_Push_Removed_Rst.json')),
        ];
    }

    #[DataProvider('createPushEventsWithDocFiles')]
    public function testDoesNotTriggersExceptionWhenDocFileWasTouched(Request $request): void
    {
        $this->subject->createPushEvent($request);
        // Plain assertion, we ensure that no exception is thrown
        $this->assertTrue(true);
    }

    public static function createPushEventFromForgejoDataProvider(): \Iterator
    {
        // Forgejo sends X-GitHub-Event and X-Gitea-Event compatibility headers
        // alongside X-Forgejo-Event, the request must still be handled as Forgejo
        yield 'Forgejo push to branch' => [
            new Request([], [], [], [], [], ['HTTP_X-Forgejo-Event' => 'push', 'HTTP_X-Gitea-Event' => 'push', 'HTTP_X-GitHub-Event' => 'push'], file_get_contents(__DIR__ . '/Fixtures/Payload_Forgejo_Event_Push_Branch.json')),
            new PushEvent('https://forgejo.example.com/acme/coolextension.git', 'main', 'https://forgejo.example.com/acme/coolextension/raw/branch/main/composer.json', '{}'),
        ];
        yield 'Forgejo push to tag' => [
            new Request([], [], [], [], [], ['HTTP_X-Forgejo-Event' => 'push', 'HTTP_X-Gitea-Event' => 'push', 'HTTP_X-GitHub-Event' => 'push'], file_get_contents(__DIR__ . '/Fixtures/Payload_Forgejo_Event_Push_Tag.json')),
            new PushEvent('https://forgejo.example.com/acme/coolextension.git', 'v1.0.0', 'https://forgejo.example.com/acme/coolextension/raw/tag/v1.0.0/composer.json', '{}'),
        ];
        yield 'Gitea push to branch' => [
            new Request([], [], [], [], [], ['HTTP_X-Gitea-Event' => 'push', 'HTTP_X-GitHub-Event' => 'push'], file_get_contents(__DIR__ . '/Fixtures/Payload_Forgejo_Event_Push_Branch.json')),
            new PushEvent('https://forgejo.example.com/acme/coolextension.git', 'main', 'https://forgejo.example.com/acme/coolextension/raw/branch/main/composer.json', '{}'),
        ];
    }

    #[DataProvider('createPushEventFromForgejoDataProvider')]
    public function testCreatePushEventFromForgejoReturnsValidPushEvent(Request $request, PushEvent $expectedPushEvent): void
    {
        $createdPushEvents = $this->subject->createPushEvent($request);
        $this->assertCount(1, $createdPushEvents);
        $this->assertSame($expectedPushEvent->getVersionString(), $createdPushEvents[0]->getVersionString());
        $this->assertSame($expectedPushEvent->getRepositoryUrl(), $createdPushEvents[0]->getRepositoryUrl());
        $this->assertSame($expectedPushEvent->getUrlToComposerFile(), $createdPushEvents[0]->getUrlToComposerFile());
    }

    public function testPlainGithubRequestIsNotHandledAsForgejo(): void
    {
        // A plain Github request carries no Forgejo/Gitea header and must keep
        // using the Github handler even though the Forgejo check runs first
        $request = new Request([], [], [], [], [], ['HTTP_X-GitHub-Event' => 'push'], file_get_contents(__DIR__ . '/Fixtures/Payload_GitHub_Event_Push_Branch.json'));

        $pushEvents = $this->subject->createPushEvent($request);

        $this->assertSame('https://raw.githubusercontent.com/Codertocat/Hello-World/main/composer.json', $pushEvents[0]->getUrlToComposerFile());
    }

    public static function deletedRefDataProvider(): \Iterator
    {
        // Forgejo sends a regular push event with an all zero 'after' when a tag
        // is deleted, it has no 'deleted' property like Github has
        yield 'sha1 repository' => [file_get_contents(__DIR__ . '/Fixtures/Payload_Forgejo_Event_Push_Tag_Deleted.json')];
        yield 'sha256 repository' => [str_replace(str_repeat('0', 40), str_repeat('0', 64), file_get_contents(__DIR__ . '/Fixtures/Payload_Forgejo_Event_Push_Tag_Deleted.json'))];
    }

    #[DataProvider('deletedRefDataProvider')]
    public function testCreatePushEventFromForgejoThrowsExceptionOnDeletedRef(string $payload): void
    {
        $request = new Request([], [], [], [], [], ['HTTP_X-Forgejo-Event' => 'push', 'HTTP_X-GitHub-Event' => 'push'], $payload);
        $this->expectException(GitBranchDeletedException::class);

        $this->subject->createPushEvent($request);
    }

    public static function notDeletedRefDataProvider(): \Iterator
    {
        // Only a full zero object id marks a deleted ref, 40 or 64 characters wide
        yield 'single zero' => ['0'];
        yield 'one zero short of sha1' => [str_repeat('0', 39)];
        yield 'one zero past sha1' => [str_repeat('0', 41)];
        yield 'one zero short of sha256' => [str_repeat('0', 63)];
        yield 'one zero past sha256' => [str_repeat('0', 65)];
    }

    #[DataProvider('notDeletedRefDataProvider')]
    public function testCreatePushEventFromForgejoDoesNotTreatPartialZerosAsDeletedRef(string $after): void
    {
        $payload = json_encode(['ref' => 'refs/heads/main', 'after' => $after, 'commits' => [['added' => ['Documentation/Index.rst']]], 'repository' => ['clone_url' => 'https://forgejo.example.com/acme/coolextension.git', 'html_url' => 'https://forgejo.example.com/acme/coolextension']], JSON_THROW_ON_ERROR);
        $request = new Request([], [], [], [], [], ['HTTP_X-Forgejo-Event' => 'push'], $payload);

        $pushEvents = $this->subject->createPushEvent($request);

        $this->assertSame('https://forgejo.example.com/acme/coolextension/raw/branch/main/composer.json', $pushEvents[0]->getUrlToComposerFile());
    }

    public static function nonStringAfterDataProvider(): \Iterator
    {
        yield 'object' => ['{"a": 1}'];
        yield 'array' => ['[1, 2]'];
        yield 'number' => ['123'];
    }

    /**
     * A non string 'after' must not reach a string cast, which would raise an
     * uncaught Error and answer a public request with a 500.
     */
    #[DataProvider('nonStringAfterDataProvider')]
    public function testCreatePushEventFromForgejoHandlesNonStringAfter(string $after): void
    {
        $payload = '{"ref":"refs/heads/main","after":' . $after . ',"commits":[{"added":["Documentation/Index.rst"]}],"repository":{"clone_url":"https://forgejo.example.com/acme/coolextension.git","html_url":"https://forgejo.example.com/acme/coolextension"}}';
        $request = new Request([], [], [], [], [], ['HTTP_X-Forgejo-Event' => 'push'], $payload);

        $pushEvents = $this->subject->createPushEvent($request);

        $this->assertSame('https://forgejo.example.com/acme/coolextension/raw/branch/main/composer.json', $pushEvents[0]->getUrlToComposerFile());
    }

    public function testCreatePushEventRendersWhenTheCommitListWasTruncated(): void
    {
        // Forgejo caps the commit list at 15, Gitea at 5, while 'total_commits'
        // keeps the real number. The documentation change may sit in a dropped
        // commit, so a truncated list must not be taken as 'nothing to render'
        $payload = json_encode(['ref' => 'refs/heads/main', 'total_commits' => 16, 'commits' => [['added' => ['src/Foo.php'], 'modified' => [], 'removed' => []]], 'repository' => ['clone_url' => 'https://forgejo.example.com/acme/coolextension.git', 'html_url' => 'https://forgejo.example.com/acme/coolextension']], JSON_THROW_ON_ERROR);
        $request = new Request([], [], [], [], [], ['HTTP_X-Forgejo-Event' => 'push'], $payload);

        $pushEvents = $this->subject->createPushEvent($request);

        $this->assertSame('https://forgejo.example.com/acme/coolextension/raw/branch/main/composer.json', $pushEvents[0]->getUrlToComposerFile());
    }

    public static function nonArrayCommitsDataProvider(): \Iterator
    {
        yield 'commits object on the github path' => ['X-GitHub-Event'];
        yield 'commits object on the forgejo path' => ['X-Forgejo-Event'];
    }

    /**
     * 'commits' is not required to be an array, so counting it for the truncation
     * check must not raise an uncaught TypeError and answer a public request
     * with a 500.
     */
    #[DataProvider('nonArrayCommitsDataProvider')]
    public function testCreatePushEventHandlesNonArrayCommits(string $header): void
    {
        $payload = '{"ref":"refs/heads/main","commits":{},"repository":{"clone_url":"https://forgejo.example.com/acme/coolextension.git","html_url":"https://forgejo.example.com/acme/coolextension","full_name":"acme/coolextension"}}';
        $request = new Request([], [], [], [], [], ['HTTP_' . $header => 'push'], $payload);
        $this->expectException(DocsNoRstChangesException::class);

        $this->subject->createPushEvent($request);
    }

    public static function invalidRefDataProvider(): \Iterator
    {
        yield 'not a full ref' => ['main'];
        yield 'neither branch nor tag' => ['refs/pull/1/head'];
        yield 'empty branch name' => ['refs/heads/'];
        yield 'empty' => [''];
    }

    #[DataProvider('invalidRefDataProvider')]
    public function testCreatePushEventFromForgejoThrowsExceptionOnUnusableRef(string $ref): void
    {
        $payload = json_encode(['ref' => $ref, 'repository' => ['clone_url' => 'https://forgejo.example.com/acme/coolextension.git', 'html_url' => 'https://forgejo.example.com/acme/coolextension']], JSON_THROW_ON_ERROR);
        $request = new Request([], [], [], [], [], ['HTTP_X-Forgejo-Event' => 'push'], $payload);
        $this->expectException(InvalidWebHookPayloadException::class);

        $this->subject->createPushEvent($request);
    }

    public function testCreatePushEventFromForgejoHandlesFormEncodedBody(): void
    {
        // Forgejo webhooks can be configured with POST content type
        // 'application/x-www-form-urlencoded', the body then is 'payload=<json>'
        $body = 'payload=' . urlencode(file_get_contents(__DIR__ . '/Fixtures/Payload_Forgejo_Event_Push_Branch.json'));
        $request = new Request([], [], [], [], [], ['HTTP_X-Forgejo-Event' => 'push', 'HTTP_X-GitHub-Event' => 'push'], $body);

        $pushEvents = $this->subject->createPushEvent($request);

        $this->assertSame('https://forgejo.example.com/acme/coolextension/raw/branch/main/composer.json', $pushEvents[0]->getUrlToComposerFile());
    }

    public function testCreatePushEventFromForgejoThrowsExceptionOnMissingHtmlUrl(): void
    {
        // Without a guard this builds a relative url, which fails later on with an
        // exception no controller catches, turning a bad payload into a 500
        $payload = json_encode(['ref' => 'refs/heads/main', 'repository' => ['clone_url' => 'https://forgejo.example.com/acme/coolextension.git']], JSON_THROW_ON_ERROR);
        $request = new Request([], [], [], [], [], ['HTTP_X-Forgejo-Event' => 'push'], $payload);
        $this->expectException(InvalidWebHookPayloadException::class);

        $this->subject->createPushEvent($request);
    }

    public function testCreatePushEventThrowsExceptionOnMissingRef(): void
    {
        $payload = json_encode(['repository' => ['clone_url' => 'https://forgejo.example.com/acme/coolextension.git', 'html_url' => 'https://forgejo.example.com/acme/coolextension']], JSON_THROW_ON_ERROR);
        $request = new Request([], [], [], [], [], ['HTTP_X-Forgejo-Event' => 'push'], $payload);
        $this->expectException(InvalidWebHookPayloadException::class);

        $this->subject->createPushEvent($request);
    }

    public static function nonObjectPayloadDataProvider(): \Iterator
    {
        yield 'number' => ['X-GitHub-Event', '123'];
        yield 'string' => ['X-GitHub-Event', '"just a string"'];
        yield 'array' => ['X-GitHub-Event', '[1, 2]'];
        yield 'boolean' => ['X-GitHub-Event', 'true'];
        yield 'number on the forgejo path' => ['X-Forgejo-Event', '123'];
        yield 'array on the forgejo path' => ['X-Gitea-Event', '[1, 2]'];
    }

    /**
     * Valid json that is not an object must not end up as an uncaught TypeError,
     * which the controller would answer with a 500 instead of a 422.
     */
    #[DataProvider('nonObjectPayloadDataProvider')]
    public function testCreatePushEventThrowsExceptionOnNonObjectPayload(string $header, string $body): void
    {
        $request = new Request([], [], [], [], [], ['HTTP_' . $header => 'push'], $body);
        $this->expectException(UnsupportedWebHookRequestException::class);

        $this->subject->createPushEvent($request);
    }

    public function testGetPushEventFromGithubThrowsException(): void
    {
        // Creates a request with json containing syntax error
        // The syntax error was generated in the json by using '' instead of "" for following key value pair "test": 'Hello',
        $request = new Request([], [], [], [], [], ['HTTP_X-GitHub-Event' => 'push'], file_get_contents(__DIR__ . '/Fixtures/Payload_GitHub_Event_Push_Added_Exception_Rst.json'));
        $this->expectException(UnsupportedWebHookRequestException::class);

        $this->subject->createPushEvent($request);
    }
}
