<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use Symfony\Component\HttpFoundation\Request;

// Forgejo and Gitea have no 'deleted' property. Deleting a tag arrives as a
// regular push event whose 'after' is the all zero object id.
return Request::create(
    '/docs',
    Request::METHOD_POST,
    [],
    [],
    [],
    ['HTTP_X-Forgejo-Event' => 'push', 'HTTP_X-Gitea-Event' => 'push', 'HTTP_X-GitHub-Event' => 'push'],
    '{
  "ref": "refs/tags/1.0.0",
  "before": "4b2b0873e595e805267eb0b41cba0b2e2b0c8a70",
  "after": "0000000000000000000000000000000000000000",
  "compare_url": "https://git.codemacher.de/",
  "commits": [

  ],
  "total_commits": 0,
  "head_commit": null,
  "repository": {
    "id": 42,
    "owner": {
      "id": 7,
      "login": "codemacher",
      "full_name": "",
      "email": "",
      "username": "codemacher"
    },
    "name": "tile_maps",
    "full_name": "codemacher/tile_maps",
    "description": "TYPO3 Extension",
    "private": false,
    "fork": false,
    "html_url": "https://git.codemacher.de/codemacher/tile_maps",
    "ssh_url": "git@git.codemacher.de:codemacher/tile_maps.git",
    "clone_url": "https://git.codemacher.de/codemacher/tile_maps.git",
    "website": "",
    "default_branch": "main",
    "archived": false
  },
  "pusher": {
    "id": 7,
    "login": "codemacher",
    "full_name": "",
    "email": "",
    "username": "codemacher"
  },
  "sender": {
    "id": 7,
    "login": "codemacher",
    "full_name": "",
    "email": "",
    "username": "codemacher"
  }
}'
);
