<?php

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
      "site_admin": false,
      "name": null,
      "company": "@TYPO3 ",
      "blog": null,
      "location": null,
      "email": "susanne.moog@gmail.com",
      "hireable": null,
      "bio": null,
      "public_repos": 12,
      "public_gists": 4,
      "followers": 7,
      "following": 5,
      "created_at": "2010-07-03T11:10:42Z",
      "updated_at": "2016-07-08T05:38:07Z"
    }'
);
