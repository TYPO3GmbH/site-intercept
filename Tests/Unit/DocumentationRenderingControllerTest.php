<?php
declare(strict_types = 1);
namespace T3G\Intercept\Tests\Unit;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use T3G\Intercept\Bamboo\Client;
use T3G\Intercept\DocumentationRenderingController;

class DocumentationRenderingControllerTest extends TestCase
{
    /**
     * @var DocumentationRenderingController
     */
    protected $subject;

    /**
     * @var Client|MockObject
     */
    protected $bambooClient;

    public function setUp()
    {
        $this->bambooClientMock = $this->getMockBuilder(Client::class)->getMock();
        $this->subject = new DocumentationRenderingController(
            $this->bambooClientMock
        );
    }

    /**
     * @test
     */
    public function triggerDocumentationPlanIsCalled()
    {
        $payload = json_encode([
            'after' => 'e47a16656dd9da2ce1589bf443b0e55033ef31ad',
            'base_ref' => null,
            'before' => '6085264b27028914a3c19e850c30d337e7049dc7',
            'commits' => [
                0 => [
                    'added' => [
                    ],
                    'author' => [
                        'email' => 'coding@daniel-siepmann.de',
                        'name' => 'Daniel Siepmann',
                        'username' => 'DanielSiepmann',
                    ],
                    'committer' => [
                        'email' => 'coding@daniel-siepmann.de',
                        'name' => 'Daniel Siepmann',
                        'username' => 'DanielSiepmann',
                    ],
                    'distinct' => true,
                    'id' => 'e47a16656dd9da2ce1589bf443b0e55033ef31ad',
                    'message' => 'TASK: Add docs about configuration of views',
                    'modified' => [
                        0 => 'CodeExamples/localPackages/example_extension/Classes/Controller/ExampleController.php',
                        1 => 'Documentation/source/Views.rst',
                    ],
                    'removed' => [
                    ],
                    'timestamp' => '2018-09-01T14:07:27+02:00',
                    'tree_id' => 'aef6fe3415846d56c5708a62a390e1401edaf6aa',
                    'url' => 'https://github.com/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/commit/e47a16656dd9da2ce1589bf443b0e55033ef31ad',
                ],
            ],
            'compare' => 'https://github.com/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/compare/6085264b2702...e47a16656dd9',
            'created' => false,
            'deleted' => false,
            'forced' => false,
            'head_commit' => [
                'added' => [
                ],
                'author' => [
                    'email' => 'coding@daniel-siepmann.de',
                    'name' => 'Daniel Siepmann',
                    'username' => 'DanielSiepmann',
                ],
                'committer' => [
                    'email' => 'coding@daniel-siepmann.de',
                    'name' => 'Daniel Siepmann',
                    'username' => 'DanielSiepmann',
                ],
                'distinct' => true,
                'id' => 'e47a16656dd9da2ce1589bf443b0e55033ef31ad',
                'message' => 'TASK: Add docs about configuration of views',
                'modified' => [
                    0 => 'CodeExamples/localPackages/example_extension/Classes/Controller/ExampleController.php',
                    1 => 'Documentation/source/Views.rst',
                ],
                'removed' => [
                ],
                'timestamp' => '2018-09-01T14:07:27+02:00',
                'tree_id' => 'aef6fe3415846d56c5708a62a390e1401edaf6aa',
                'url' => 'https://github.com/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/commit/e47a16656dd9da2ce1589bf443b0e55033ef31ad',
            ],
            'pusher' => [
                'email' => 'coding@daniel-siepmann.de',
                'name' => 'DanielSiepmann',
            ],
            'ref' => 'refs/heads/latest',
            'repository' => [
                'archive_url' => 'https://api.github.com/repos/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/{archive_format}{/ref}',
                'archived' => false,
                'assignees_url' => 'https://api.github.com/repos/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/assignees{/user}',
                'blobs_url' => 'https://api.github.com/repos/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/git/blobs{/sha}',
                'branches_url' => 'https://api.github.com/repos/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/branches{/branch}',
                'clone_url' => 'https://github.com/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript.git',
                'collaborators_url' => 'https://api.github.com/repos/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/collaborators{/collaborator}',
                'comments_url' => 'https://api.github.com/repos/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/comments{/number}',
                'commits_url' => 'https://api.github.com/repos/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/commits{/sha}',
                'compare_url' => 'https://api.github.com/repos/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/compare/{base}...{head}',
                'contents_url' => 'https://api.github.com/repos/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/contents/{+path}',
                'contributors_url' => 'https://api.github.com/repos/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/contributors',
                'created_at' => 1534153162,
                'default_branch' => 'master',
                'deployments_url' => 'https://api.github.com/repos/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/deployments',
                'description' => 'Material for TYPO3 Extension workshop',
                'downloads_url' => 'https://api.github.com/repos/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/downloads',
                'events_url' => 'https://api.github.com/repos/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/events',
                'fork' => false,
                'forks' => 0,
                'forks_count' => 0,
                'forks_url' => 'https://api.github.com/repos/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/forks',
                'full_name' => 'TYPO3-Documentation/TYPO3CMS-Reference-Typoscript',
                'git_commits_url' => 'https://api.github.com/repos/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/git/commits{/sha}',
                'git_refs_url' => 'https://api.github.com/repos/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/git/refs{/sha}',
                'git_tags_url' => 'https://api.github.com/repos/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/git/tags{/sha}',
                'git_url' => 'git://github.com/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript.git',
                'has_downloads' => true,
                'has_issues' => true,
                'has_pages' => false,
                'has_projects' => false,
                'has_wiki' => false,
                'homepage' => '',
                'hooks_url' => 'https://api.github.com/repos/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/hooks',
                'html_url' => 'https://github.com/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript',
                'id' => 144559114,
                'issue_comment_url' => 'https://api.github.com/repos/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/issues/comments{/number}',
                'issue_events_url' => 'https://api.github.com/repos/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/issues/events{/number}',
                'issues_url' => 'https://api.github.com/repos/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/issues{/number}',
                'keys_url' => 'https://api.github.com/repos/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/keys{/key_id}',
                'labels_url' => 'https://api.github.com/repos/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/labels{/name}',
                'language' => null,
                'languages_url' => 'https://api.github.com/repos/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/languages',
                'license' => [
                    'key' => 'gpl-2.0',
                    'name' => 'GNU General Public License v2.0',
                    'node_id' => 'MDc6TGljZW5zZTg=',
                    'spdx_id' => 'GPL-2.0',
                    'url' => 'https://api.github.com/licenses/gpl-2.0',
                ],
                'master_branch' => 'master',
                'merges_url' => 'https://api.github.com/repos/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/merges',
                'milestones_url' => 'https://api.github.com/repos/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/milestones{/number}',
                'mirror_url' => null,
                'name' => 'typo3-extension-workshop',
                'node_id' => 'MDEwOlJlcG9zaXRvcnkxNDQ1NTkxMTQ=',
                'notifications_url' => 'https://api.github.com/repos/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/notifications{?since,all,participating}',
                'open_issues' => 0,
                'open_issues_count' => 0,
                'owner' => [
                    'avatar_url' => 'https://avatars3.githubusercontent.com/u/354250?v=4',
                    'email' => 'coding@daniel-siepmann.de',
                    'events_url' => 'https://api.github.com/users/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/privacy}',
                    'followers_url' => 'https://api.github.com/users/DanielSiepmann/followers',
                    'following_url' => 'https://api.github.com/users/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/other_user}',
                    'gists_url' => 'https://api.github.com/users/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/gist_id}',
                    'gravatar_id' => '',
                    'html_url' => 'https://github.com/DanielSiepmann',
                    'id' => 354250,
                    'login' => 'DanielSiepmann',
                    'name' => 'DanielSiepmann',
                    'node_id' => 'MDQ6VXNlcjM1NDI1MA==',
                    'organizations_url' => 'https://api.github.com/users/DanielSiepmann/orgs',
                    'received_events_url' => 'https://api.github.com/users/DanielSiepmann/received_events',
                    'repos_url' => 'https://api.github.com/users/DanielSiepmann/repos',
                    'site_admin' => false,
                    'starred_url' => 'https://api.github.com/users/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/owner}{/repo}',
                    'subscriptions_url' => 'https://api.github.com/users/DanielSiepmann/subscriptions',
                    'type' => 'User',
                    'url' => 'https://api.github.com/users/DanielSiepmann',
                ],
                'private' => false,
                'pulls_url' => 'https://api.github.com/repos/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/pulls{/number}',
                'pushed_at' => 1535803668,
                'releases_url' => 'https://api.github.com/repos/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/releases{/id}',
                'size' => 43,
                'ssh_url' => 'git@github.com:TYPO3-Documentation/TYPO3CMS-Reference-Typoscript.git',
                'stargazers' => 0,
                'stargazers_count' => 0,
                'stargazers_url' => 'https://api.github.com/repos/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/stargazers',
                'statuses_url' => 'https://api.github.com/repos/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/statuses/{sha}',
                'subscribers_url' => 'https://api.github.com/repos/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/subscribers',
                'subscription_url' => 'https://api.github.com/repos/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/subscription',
                'svn_url' => 'https://github.com/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript',
                'tags_url' => 'https://api.github.com/repos/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/tags',
                'teams_url' => 'https://api.github.com/repos/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/teams',
                'trees_url' => 'https://api.github.com/repos/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript/git/trees{/sha}',
                'updated_at' => '2018-08-13T10:27:56Z',
                'url' => 'https://github.com/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript',
                'watchers' => 0,
                'watchers_count' => 0,
            ],
            'sender' => [
                'avatar_url' => 'https://avatars3.githubusercontent.com/u/354250?v=4',
                'events_url' => 'https://api.github.com/users/DanielSiepmann/events{/privacy}',
                'followers_url' => 'https://api.github.com/users/DanielSiepmann/followers',
                'following_url' => 'https://api.github.com/users/DanielSiepmann/following{/other_user}',
                'gists_url' => 'https://api.github.com/users/DanielSiepmann/gists{/gist_id}',
                'gravatar_id' => '',
                'html_url' => 'https://github.com/DanielSiepmann',
                'id' => 354250,
                'login' => 'DanielSiepmann',
                'node_id' => 'MDQ6VXNlcjM1NDI1MA==',
                'organizations_url' => 'https://api.github.com/users/DanielSiepmann/orgs',
                'received_events_url' => 'https://api.github.com/users/DanielSiepmann/received_events',
                'repos_url' => 'https://api.github.com/users/DanielSiepmann/repos',
                'site_admin' => false,
                'starred_url' => 'https://api.github.com/users/DanielSiepmann/starred{/owner}{/repo}',
                'subscriptions_url' => 'https://api.github.com/users/DanielSiepmann/subscriptions',
                'type' => 'User',
                'url' => 'https://api.github.com/users/DanielSiepmann',
            ],
        ]);

        $this->bambooClientMock->expects($this->once())
            ->method('triggerDocumentationPlan')
            ->with($this->callback(function ($renderingRequest) {
                return $renderingRequest->getVersionNumber() === 'latest'
                    && $renderingRequest->getRepositoryUrl() === 'https://github.com/TYPO3-Documentation/TYPO3CMS-Reference-Typoscript.git'
                    ;
            }));

        $this->subject->transformGithubWebhookIntoRenderingRequest($payload);
    }
}
