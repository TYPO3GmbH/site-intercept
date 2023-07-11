<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use GuzzleHttp\Psr7\Response;

return new Response(
    200,
    [],
    '{
      "url": "https://api.github.com/repos/psychomieze/TYPO3.CMS/issues/1",
      "repository_url": "https://api.github.com/repos/psychomieze/TYPO3.CMS",
      "labels_url": "https://api.github.com/repos/psychomieze/TYPO3.CMS/issues/1/labels{/name}",
      "comments_url": "https://api.github.com/repos/psychomieze/TYPO3.CMS/issues/1/comments",
      "events_url": "https://api.github.com/repos/psychomieze/TYPO3.CMS/issues/1/events",
      "html_url": "https://github.com/psychomieze/TYPO3.CMS/pull/1",
      "id": 164110472,
      "number": 1,
      "title": "issue title",
      "user": {
        "login": "psychomieze",
        "id": 321804,
        "avatar_url": "https://avatars.githubusercontent.com/u/321804?v=3",
        "gravatar_id": "",
        "url": "https://api.github.com/users/psychomieze",
        "html_url": "https://github.com/psychomieze",
        "followers_url": "https://api.github.com/users/psychomieze/followers",
        "following_url": "https://api.github.com/users/psychomieze/following{/other_user}",
        "gists_url": "https://api.github.com/users/psychomieze/gists{/gist_id}",
        "starred_url": "https://api.github.com/users/psychomieze/starred{/owner}{/repo}",
        "subscriptions_url": "https://api.github.com/users/psychomieze/subscriptions",
        "organizations_url": "https://api.github.com/users/psychomieze/orgs",
        "repos_url": "https://api.github.com/users/psychomieze/repos",
        "events_url": "https://api.github.com/users/psychomieze/events{/privacy}",
        "received_events_url": "https://api.github.com/users/psychomieze/received_events",
        "type": "User",
        "site_admin": false
      },
      "labels": [
    
      ],
      "state": "closed",
      "locked": false,
      "assignee": null,
      "assignees": [
    
      ],
      "milestone": null,
      "comments": 1,
      "created_at": "2016-07-06T15:53:53Z",
      "updated_at": "2016-07-11T06:31:46Z",
      "closed_at": "2016-07-11T06:25:14Z",
      "pull_request": {
        "url": "https://api.github.com/repos/psychomieze/TYPO3.CMS/pulls/1",
        "html_url": "https://github.com/psychomieze/TYPO3.CMS/pull/1",
        "diff_url": "https://github.com/psychomieze/TYPO3.CMS/pull/1.diff",
        "patch_url": "https://github.com/psychomieze/TYPO3.CMS/pull/1.patch"
      },
      "body": "updated body",
      "closed_by": {
        "login": "psychomieze",
        "id": 321804,
        "avatar_url": "https://avatars.githubusercontent.com/u/321804?v=3",
        "gravatar_id": "",
        "url": "https://api.github.com/users/psychomieze",
        "html_url": "https://github.com/psychomieze",
        "followers_url": "https://api.github.com/users/psychomieze/followers",
        "following_url": "https://api.github.com/users/psychomieze/following{/other_user}",
        "gists_url": "https://api.github.com/users/psychomieze/gists{/gist_id}",
        "starred_url": "https://api.github.com/users/psychomieze/starred{/owner}{/repo}",
        "subscriptions_url": "https://api.github.com/users/psychomieze/subscriptions",
        "organizations_url": "https://api.github.com/users/psychomieze/orgs",
        "repos_url": "https://api.github.com/users/psychomieze/repos",
        "events_url": "https://api.github.com/users/psychomieze/events{/privacy}",
        "received_events_url": "https://api.github.com/users/psychomieze/received_events",
        "type": "User",
        "site_admin": false
      }
    }'
);
