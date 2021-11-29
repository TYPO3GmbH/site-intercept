<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use Symfony\Component\HttpFoundation\Request;

return Request::create(
    '/docs',
    'POST',
    [],
    [],
    [],
    ['HTTP_X-Event-Key' => 'repo:push'],
    '{
  "push": {
    "changes": [
      {
        "forced": false,
        "old": {
          "name": "main",
          "links": {
            "commits": {
              "href": "https://api.bitbucket.org/2.0/repositories/pathfindermediagroup/eso-export-addon/commits/main"
            },
            "self": {
              "href": "https://api.bitbucket.org/2.0/repositories/pathfindermediagroup/eso-export-addon/refs/branches/main"
            },
            "html": {
              "href": "https://bitbucket.org/pathfindermediagroup/eso-export-addon/branch/main"
            }
          },
          "default_merge_strategy": "merge_commit",
          "merge_strategies": [
            "merge_commit",
            "squash",
            "fast_forward"
          ],
          "type": "branch",
          "target": {
            "rendered": {},
            "hash": "2c635362996ca85d6a0fd353d4d39d70e8a88c61",
            "links": {
              "self": {
                "href": "https://api.bitbucket.org/2.0/repositories/pathfindermediagroup/eso-export-addon/commit/2c635362996ca85d6a0fd353d4d39d70e8a88c61"
              },
              "html": {
                "href": "https://bitbucket.org/pathfindermediagroup/eso-export-addon/commits/2c635362996ca85d6a0fd353d4d39d70e8a88c61"
              }
            },
            "author": {
              "raw": "Woeler <woeler@woeler.eu>",
              "type": "author"
            },
            "summary": {
              "raw": "v1.1\n",
              "markup": "markdown",
              "html": "<p>v1.1</p>",
              "type": "rendered"
            },
            "parents": [],
            "date": "2019-06-07T08:39:50+00:00",
            "message": "v1.1\n",
            "type": "commit",
            "properties": {}
          }
        },
        "links": {
          "commits": {
            "href": "https://api.bitbucket.org/2.0/repositories/pathfindermediagroup/eso-export-addon/commits?include=75c914db86a7224daf6ef9a6ccb107959fa3e015&exclude=080d872f81cd1c5779c7d154129c24c3dd5ee12d"
          },
          "html": {
            "href": "https://bitbucket.org/pathfindermediagroup/eso-export-addon/branches/compare/75c914db86a7224daf6ef9a6ccb107959fa3e015..080d872f81cd1c5779c7d154129c24c3dd5ee12d"
          },
          "diff": {
            "href": "https://api.bitbucket.org/2.0/repositories/pathfindermediagroup/eso-export-addon/diff/75c914db86a7224daf6ef9a6ccb107959fa3e015..080d872f81cd1c5779c7d154129c24c3dd5ee12d"
          }
        },
        "created": false,
        "commits": [
          {
            "rendered": {},
            "hash": "9dabe5da4aab73e75ddc8b8ba996cbeba4fcd2a1",
            "links": {
              "self": {
                "href": "https://api.bitbucket.org/2.0/repositories/pathfindermediagroup/eso-export-addon/commit/9dabe5da4aab73e75ddc8b8ba996cbeba4fcd2a1"
              },
              "comments": {
                "href": "https://api.bitbucket.org/2.0/repositories/pathfindermediagroup/eso-export-addon/commit/9dabe5da4aab73e75ddc8b8ba996cbeba4fcd2a1/comments"
              },
              "patch": {
                "href": "https://api.bitbucket.org/2.0/repositories/pathfindermediagroup/eso-export-addon/patch/9dabe5da4aab73e75ddc8b8ba996cbeba4fcd2a1"
              },
              "html": {
                "href": "https://bitbucket.org/pathfindermediagroup/eso-export-addon/commits/9dabe5da4aab73e75ddc8b8ba996cbeba4fcd2a1"
              },
              "diff": {
                "href": "https://api.bitbucket.org/2.0/repositories/pathfindermediagroup/eso-export-addon/diff/9dabe5da4aab73e75ddc8b8ba996cbeba4fcd2a1"
              },
              "approve": {
                "href": "https://api.bitbucket.org/2.0/repositories/pathfindermediagroup/eso-export-addon/commit/9dabe5da4aab73e75ddc8b8ba996cbeba4fcd2a1/approve"
              },
              "statuses": {
                "href": "https://api.bitbucket.org/2.0/repositories/pathfindermediagroup/eso-export-addon/commit/9dabe5da4aab73e75ddc8b8ba996cbeba4fcd2a1/statuses"
              }
            },
            "author": {
              "raw": "Woeler <woeler@woeler.eu>",
              "type": "author"
            },
            "summary": {
              "raw": "lidl\n",
              "markup": "markdown",
              "html": "<p>lidl</p>",
              "type": "rendered"
            },
            "parents": [
              {
                "hash": "2c635362996ca85d6a0fd353d4d39d70e8a88c61",
                "type": "commit",
                "links": {
                  "self": {
                    "href": "https://api.bitbucket.org/2.0/repositories/pathfindermediagroup/eso-export-addon/commit/2c635362996ca85d6a0fd353d4d39d70e8a88c61"
                  },
                  "html": {
                    "href": "https://bitbucket.org/pathfindermediagroup/eso-export-addon/commits/2c635362996ca85d6a0fd353d4d39d70e8a88c61"
                  }
                }
              }
            ],
            "date": "2019-06-07T08:43:13+00:00",
            "message": "lidl\n",
            "type": "commit",
            "properties": {}
          }
        ],
        "truncated": false,
        "closed": false,
        "new": {
          "name": "main",
          "links": {
            "commits": {
              "href": "https://api.bitbucket.org/2.0/repositories/pathfindermediagroup/eso-export-addon/commits/main"
            },
            "self": {
              "href": "https://api.bitbucket.org/2.0/repositories/pathfindermediagroup/eso-export-addon/refs/branches/main"
            },
            "html": {
              "href": "https://bitbucket.org/pathfindermediagroup/eso-export-addon/branch/main"
            }
          },
          "default_merge_strategy": "merge_commit",
          "merge_strategies": [
            "merge_commit",
            "squash",
            "fast_forward"
          ],
          "type": "branch",
          "target": {
            "rendered": {},
            "hash": "9dabe5da4aab73e75ddc8b8ba996cbeba4fcd2a1",
            "links": {
              "self": {
                "href": "https://api.bitbucket.org/2.0/repositories/pathfindermediagroup/eso-export-addon/commit/9dabe5da4aab73e75ddc8b8ba996cbeba4fcd2a1"
              },
              "html": {
                "href": "https://bitbucket.org/pathfindermediagroup/eso-export-addon/commits/9dabe5da4aab73e75ddc8b8ba996cbeba4fcd2a1"
              }
            },
            "author": {
              "raw": "Woeler <woeler@woeler.eu>",
              "type": "author"
            },
            "summary": {
              "raw": "lidl\n",
              "markup": "markdown",
              "html": "<p>lidl</p>",
              "type": "rendered"
            },
            "parents": [
              {
                "hash": "2c635362996ca85d6a0fd353d4d39d70e8a88c61",
                "type": "commit",
                "links": {
                  "self": {
                    "href": "https://api.bitbucket.org/2.0/repositories/pathfindermediagroup/eso-export-addon/commit/2c635362996ca85d6a0fd353d4d39d70e8a88c61"
                  },
                  "html": {
                    "href": "https://bitbucket.org/pathfindermediagroup/eso-export-addon/commits/2c635362996ca85d6a0fd353d4d39d70e8a88c61"
                  }
                }
              }
            ],
            "date": "2019-06-07T08:43:13+00:00",
            "message": "lidl\n",
            "type": "commit",
            "properties": {}
          }
        }
      },
      {
        "forced": false,
        "old": {
          "name": "v1.1",
          "links": {
            "commits": {
              "href": "https://api.bitbucket.org/2.0/repositories/pathfindermediagroup/eso-export-addon/commits/v1.1"
            },
            "self": {
              "href": "https://api.bitbucket.org/2.0/repositories/pathfindermediagroup/eso-export-addon/refs/branches/v1.1"
            },
            "html": {
              "href": "https://bitbucket.org/pathfindermediagroup/eso-export-addon/branch/v1.1"
            }
          },
          "default_merge_strategy": "merge_commit",
          "merge_strategies": [
            "merge_commit",
            "squash",
            "fast_forward"
          ],
          "type": "branch",
          "target": {
            "rendered": {},
            "hash": "080d872f81cd1c5779c7d154129c24c3dd5ee12d",
            "links": {
              "self": {
                "href": "https://api.bitbucket.org/2.0/repositories/pathfindermediagroup/eso-export-addon/commit/080d872f81cd1c5779c7d154129c24c3dd5ee12d"
              },
              "html": {
                "href": "https://bitbucket.org/pathfindermediagroup/eso-export-addon/commits/080d872f81cd1c5779c7d154129c24c3dd5ee12d"
              }
            },
            "author": {
              "raw": "Woeler <woeler@woeler.eu>",
              "type": "author"
            },
            "summary": {
              "raw": "v1.12\n",
              "markup": "markdown",
              "html": "<p>v1.12</p>",
              "type": "rendered"
            },
            "parents": [
              {
                "hash": "2c635362996ca85d6a0fd353d4d39d70e8a88c61",
                "type": "commit",
                "links": {
                  "self": {
                    "href": "https://api.bitbucket.org/2.0/repositories/pathfindermediagroup/eso-export-addon/commit/2c635362996ca85d6a0fd353d4d39d70e8a88c61"
                  },
                  "html": {
                    "href": "https://bitbucket.org/pathfindermediagroup/eso-export-addon/commits/2c635362996ca85d6a0fd353d4d39d70e8a88c61"
                  }
                }
              }
            ],
            "date": "2019-06-07T08:40:12+00:00",
            "message": "v1.12\n",
            "type": "commit",
            "properties": {}
          }
        },
        "links": {
          "commits": {
            "href": "https://api.bitbucket.org/2.0/repositories/pathfindermediagroup/eso-export-addon/commits?include=75c914db86a7224daf6ef9a6ccb107959fa3e015&exclude=080d872f81cd1c5779c7d154129c24c3dd5ee12d"
          },
          "html": {
            "href": "https://bitbucket.org/pathfindermediagroup/eso-export-addon/branches/compare/75c914db86a7224daf6ef9a6ccb107959fa3e015..080d872f81cd1c5779c7d154129c24c3dd5ee12d"
          },
          "diff": {
            "href": "https://api.bitbucket.org/2.0/repositories/pathfindermediagroup/eso-export-addon/diff/75c914db86a7224daf6ef9a6ccb107959fa3e015..080d872f81cd1c5779c7d154129c24c3dd5ee12d"
          }
        },
        "created": false,
        "commits": [
          {
            "rendered": {},
            "hash": "75c914db86a7224daf6ef9a6ccb107959fa3e015",
            "links": {
              "self": {
                "href": "https://api.bitbucket.org/2.0/repositories/pathfindermediagroup/eso-export-addon/commit/75c914db86a7224daf6ef9a6ccb107959fa3e015"
              },
              "comments": {
                "href": "https://api.bitbucket.org/2.0/repositories/pathfindermediagroup/eso-export-addon/commit/75c914db86a7224daf6ef9a6ccb107959fa3e015/comments"
              },
              "patch": {
                "href": "https://api.bitbucket.org/2.0/repositories/pathfindermediagroup/eso-export-addon/patch/75c914db86a7224daf6ef9a6ccb107959fa3e015"
              },
              "html": {
                "href": "https://bitbucket.org/pathfindermediagroup/eso-export-addon/commits/75c914db86a7224daf6ef9a6ccb107959fa3e015"
              },
              "diff": {
                "href": "https://api.bitbucket.org/2.0/repositories/pathfindermediagroup/eso-export-addon/diff/75c914db86a7224daf6ef9a6ccb107959fa3e015"
              },
              "approve": {
                "href": "https://api.bitbucket.org/2.0/repositories/pathfindermediagroup/eso-export-addon/commit/75c914db86a7224daf6ef9a6ccb107959fa3e015/approve"
              },
              "statuses": {
                "href": "https://api.bitbucket.org/2.0/repositories/pathfindermediagroup/eso-export-addon/commit/75c914db86a7224daf6ef9a6ccb107959fa3e015/statuses"
              }
            },
            "author": {
              "raw": "Woeler <woeler@woeler.eu>",
              "type": "author"
            },
            "summary": {
              "raw": "bla\n",
              "markup": "markdown",
              "html": "<p>bla</p>",
              "type": "rendered"
            },
            "parents": [
              {
                "hash": "080d872f81cd1c5779c7d154129c24c3dd5ee12d",
                "type": "commit",
                "links": {
                  "self": {
                    "href": "https://api.bitbucket.org/2.0/repositories/pathfindermediagroup/eso-export-addon/commit/080d872f81cd1c5779c7d154129c24c3dd5ee12d"
                  },
                  "html": {
                    "href": "https://bitbucket.org/pathfindermediagroup/eso-export-addon/commits/080d872f81cd1c5779c7d154129c24c3dd5ee12d"
                  }
                }
              }
            ],
            "date": "2019-06-07T08:43:32+00:00",
            "message": "bla\n",
            "type": "commit",
            "properties": {}
          }
        ],
        "truncated": false,
        "closed": false,
        "new": {
          "name": "v1.1",
          "links": {
            "commits": {
              "href": "https://api.bitbucket.org/2.0/repositories/pathfindermediagroup/eso-export-addon/commits/v1.1"
            },
            "self": {
              "href": "https://api.bitbucket.org/2.0/repositories/pathfindermediagroup/eso-export-addon/refs/branches/v1.1"
            },
            "html": {
              "href": "https://bitbucket.org/pathfindermediagroup/eso-export-addon/branch/v1.1"
            }
          },
          "default_merge_strategy": "merge_commit",
          "merge_strategies": [
            "merge_commit",
            "squash",
            "fast_forward"
          ],
          "type": "branch",
          "target": {
            "rendered": {},
            "hash": "75c914db86a7224daf6ef9a6ccb107959fa3e015",
            "links": {
              "self": {
                "href": "https://api.bitbucket.org/2.0/repositories/pathfindermediagroup/eso-export-addon/commit/75c914db86a7224daf6ef9a6ccb107959fa3e015"
              },
              "html": {
                "href": "https://bitbucket.org/pathfindermediagroup/eso-export-addon/commits/75c914db86a7224daf6ef9a6ccb107959fa3e015"
              }
            },
            "author": {
              "raw": "Woeler <woeler@woeler.eu>",
              "type": "author"
            },
            "summary": {
              "raw": "bla\n",
              "markup": "markdown",
              "html": "<p>bla</p>",
              "type": "rendered"
            },
            "parents": [
              {
                "hash": "080d872f81cd1c5779c7d154129c24c3dd5ee12d",
                "type": "commit",
                "links": {
                  "self": {
                    "href": "https://api.bitbucket.org/2.0/repositories/pathfindermediagroup/eso-export-addon/commit/080d872f81cd1c5779c7d154129c24c3dd5ee12d"
                  },
                  "html": {
                    "href": "https://bitbucket.org/pathfindermediagroup/eso-export-addon/commits/080d872f81cd1c5779c7d154129c24c3dd5ee12d"
                  }
                }
              }
            ],
            "date": "2019-06-07T08:43:32+00:00",
            "message": "bla\n",
            "type": "commit",
            "properties": {}
          }
        }
      }
    ]
  },
  "actor": {
    "username": "woeler-pmg",
    "display_name": "Jurian Janssen",
    "uuid": "{c32602c8-c6a7-4423-aebc-7c5db5600daa}",
    "links": {
      "self": {
        "href": "https://api.bitbucket.org/2.0/users/%7Bc32602c8-c6a7-4423-aebc-7c5db5600daa%7D"
      },
      "html": {
        "href": "https://bitbucket.org/%7Bc32602c8-c6a7-4423-aebc-7c5db5600daa%7D/"
      },
      "avatar": {
        "href": "https://avatar-cdn.atlassian.com/5ccc48532f51be0e56a1f489?by=id&sg=T8fzWvna%2BhUqya%2B9bXUmXkQq%2F08%3D&d=https%3A%2F%2Favatar-management--avatars.us-west-2.prod.public.atl-paas.net%2Finitials%2FJJ-2.svg"
      }
    },
    "nickname": "Jurian Janssen",
    "type": "user",
    "account_id": "5ccc48532f51be0e56a1f489"
  },
  "repository": {
    "scm": "git",
    "website": "",
    "name": "eso-export-addon",
    "links": {
      "self": {
        "href": "https://api.bitbucket.org/2.0/repositories/pathfindermediagroup/eso-export-addon"
      },
      "html": {
        "href": "https://bitbucket.org/pathfindermediagroup/eso-export-addon"
      },
      "avatar": {
        "href": "https://bytebucket.org/ravatar/%7Ba3ba3ba8-d71e-4767-b874-4d0e7f78b35f%7D?ts=default"
      }
    },
    "project": {
      "key": "EP",
      "type": "project",
      "uuid": "{4a8b7be9-c653-40c1-b3f5-820335f5c701}",
      "links": {
        "self": {
          "href": "https://api.bitbucket.org/2.0/teams/pathfindermediagroup/projects/EP"
        },
        "html": {
          "href": "https://bitbucket.org/account/user/pathfindermediagroup/projects/EP"
        },
        "avatar": {
          "href": "https://bitbucket.org/account/user/pathfindermediagroup/projects/EP/avatar/32"
        }
      },
      "name": "ESO Projects"
    },
    "full_name": "pathfindermediagroup/eso-export-addon",
    "owner": {
      "username": "pathfindermediagroup",
      "display_name": "Pathfinder Media Group",
      "type": "team",
      "uuid": "{9518493c-3519-4ca7-9017-8ec03e697fe5}",
      "links": {
        "self": {
          "href": "https://api.bitbucket.org/2.0/teams/%7B9518493c-3519-4ca7-9017-8ec03e697fe5%7D"
        },
        "html": {
          "href": "https://bitbucket.org/%7B9518493c-3519-4ca7-9017-8ec03e697fe5%7D/"
        },
        "avatar": {
          "href": "https://bitbucket.org/account/pathfindermediagroup/avatar/"
        }
      }
    },
    "type": "repository",
    "is_private": true,
    "uuid": "{a3ba3ba8-d71e-4767-b874-4d0e7f78b35f}"
  }
}'
);
