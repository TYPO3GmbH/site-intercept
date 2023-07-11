<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use Symfony\Component\HttpFoundation\Request;

return Request::create(
    '/docs',
    \Symfony\Component\HttpFoundation\Request::METHOD_POST,
    [],
    [],
    [],
    ['HTTP_X-GitHub-Event' => 'ping'],
    '{
      "zen": "Anything added dilutes everything else.",
      "hook_id": 109640714,
      "hook": {
        "type": "Repository",
        "id": 109640714,
        "name": "web",
        "active": true,
        "events": [
          "push"
        ],
        "config": {
          "content_type": "json",
          "insecure_ssl": "0",
          "url": "https://docs-hook.typo3.org/"
        },
        "updated_at": "2019-05-14T09:53:58Z",
        "created_at": "2019-05-14T09:53:58Z",
        "url": "https://api.github.com/repos/benjaminkott/bootstrap_package/hooks/109640714",
        "test_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/hooks/109640714/test",
        "ping_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/hooks/109640714/pings",
        "last_response": {
          "code": null,
          "status": "unused",
          "message": null
        }
      },
      "repository": {
        "id": 17935139,
        "node_id": "MDEwOlJlcG9zaXRvcnkxNzkzNTEzOQ==",
        "name": "bootstrap_package",
        "full_name": "benjaminkott/bootstrap_package",
        "private": false,
        "owner": {
          "login": "benjaminkott",
          "id": 3243119,
          "node_id": "MDQ6VXNlcjMyNDMxMTk=",
          "avatar_url": "https://avatars0.githubusercontent.com/u/3243119?v=4",
          "gravatar_id": "",
          "url": "https://api.github.com/users/benjaminkott",
          "html_url": "https://github.com/benjaminkott",
          "followers_url": "https://api.github.com/users/benjaminkott/followers",
          "following_url": "https://api.github.com/users/benjaminkott/following{/other_user}",
          "gists_url": "https://api.github.com/users/benjaminkott/gists{/gist_id}",
          "starred_url": "https://api.github.com/users/benjaminkott/starred{/owner}{/repo}",
          "subscriptions_url": "https://api.github.com/users/benjaminkott/subscriptions",
          "organizations_url": "https://api.github.com/users/benjaminkott/orgs",
          "repos_url": "https://api.github.com/users/benjaminkott/repos",
          "events_url": "https://api.github.com/users/benjaminkott/events{/privacy}",
          "received_events_url": "https://api.github.com/users/benjaminkott/received_events",
          "type": "User",
          "site_admin": false
        },
        "html_url": "https://github.com/benjaminkott/bootstrap_package",
        "description": "Bootstrap Package delivers a full configured theme for TYPO3, based on the Bootstrap CSS Framework.",
        "fork": false,
        "url": "https://api.github.com/repos/benjaminkott/bootstrap_package",
        "forks_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/forks",
        "keys_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/keys{/key_id}",
        "collaborators_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/collaborators{/collaborator}",
        "teams_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/teams",
        "hooks_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/hooks",
        "issue_events_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/issues/events{/number}",
        "events_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/events",
        "assignees_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/assignees{/user}",
        "branches_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/branches{/branch}",
        "tags_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/tags",
        "blobs_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/git/blobs{/sha}",
        "git_tags_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/git/tags{/sha}",
        "git_refs_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/git/refs{/sha}",
        "trees_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/git/trees{/sha}",
        "statuses_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/statuses/{sha}",
        "languages_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/languages",
        "stargazers_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/stargazers",
        "contributors_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/contributors",
        "subscribers_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/subscribers",
        "subscription_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/subscription",
        "commits_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/commits{/sha}",
        "git_commits_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/git/commits{/sha}",
        "comments_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/comments{/number}",
        "issue_comment_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/issues/comments{/number}",
        "contents_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/contents/{+path}",
        "compare_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/compare/{base}...{head}",
        "merges_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/merges",
        "archive_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/{archive_format}{/ref}",
        "downloads_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/downloads",
        "issues_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/issues{/number}",
        "pulls_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/pulls{/number}",
        "milestones_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/milestones{/number}",
        "notifications_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/notifications{?since,all,participating}",
        "labels_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/labels{/name}",
        "releases_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/releases{/id}",
        "deployments_url": "https://api.github.com/repos/benjaminkott/bootstrap_package/deployments",
        "created_at": "2014-03-20T08:14:28Z",
        "updated_at": "2019-04-23T19:47:09Z",
        "pushed_at": "2019-04-23T19:47:07Z",
        "git_url": "git://github.com/benjaminkott/bootstrap_package.git",
        "ssh_url": "git@github.com:benjaminkott/bootstrap_package.git",
        "clone_url": "https://github.com/benjaminkott/bootstrap_package.git",
        "svn_url": "https://github.com/benjaminkott/bootstrap_package",
        "homepage": "http://www.bootstrap-package.com/",
        "size": 11548,
        "stargazers_count": 200,
        "watchers_count": 200,
        "language": "PHP",
        "has_issues": true,
        "has_projects": true,
        "has_downloads": true,
        "has_wiki": true,
        "has_pages": false,
        "forks_count": 148,
        "mirror_url": null,
        "archived": false,
        "disabled": false,
        "open_issues_count": 59,
        "license": {
          "key": "mit",
          "name": "MIT License",
          "spdx_id": "MIT",
          "url": "https://api.github.com/licenses/mit",
          "node_id": "MDc6TGljZW5zZTEz"
        },
        "forks": 148,
        "open_issues": 59,
        "watchers": 200,
        "default_branch": "main"
      },
      "sender": {
        "login": "benjaminkott",
        "id": 3243119,
        "node_id": "MDQ6VXNlcjMyNDMxMTk=",
        "avatar_url": "https://avatars0.githubusercontent.com/u/3243119?v=4",
        "gravatar_id": "",
        "url": "https://api.github.com/users/benjaminkott",
        "html_url": "https://github.com/benjaminkott",
        "followers_url": "https://api.github.com/users/benjaminkott/followers",
        "following_url": "https://api.github.com/users/benjaminkott/following{/other_user}",
        "gists_url": "https://api.github.com/users/benjaminkott/gists{/gist_id}",
        "starred_url": "https://api.github.com/users/benjaminkott/starred{/owner}{/repo}",
        "subscriptions_url": "https://api.github.com/users/benjaminkott/subscriptions",
        "organizations_url": "https://api.github.com/users/benjaminkott/orgs",
        "repos_url": "https://api.github.com/users/benjaminkott/repos",
        "events_url": "https://api.github.com/users/benjaminkott/events{/privacy}",
        "received_events_url": "https://api.github.com/users/benjaminkott/received_events",
        "type": "User",
        "site_admin": false
      }
    }'
);
