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
        "url": "https://api.github.com/repos/TYPO3/typo3/compare/cc9ce47f0c030772ca6b59bbc65d728fb32c96dd...47520511f4947a6ebd139a84e831a062a5b61c31",
        "html_url": "https://github.com/TYPO3/typo3/compare/cc9ce47f0c030772ca6b59bbc65d728fb32c96dd...47520511f4947a6ebd139a84e831a062a5b61c31",
        "permalink_url": "https://github.com/TYPO3/typo3/compare/TYPO3:cc9ce47...TYPO3:4752051",
        "diff_url": "https://github.com/TYPO3/typo3/compare/cc9ce47f0c030772ca6b59bbc65d728fb32c96dd...47520511f4947a6ebd139a84e831a062a5b61c31.diff",
        "patch_url": "https://github.com/TYPO3/typo3/compare/cc9ce47f0c030772ca6b59bbc65d728fb32c96dd...47520511f4947a6ebd139a84e831a062a5b61c31.patch",
        "base_commit": {
                "sha": "cc9ce47f0c030772ca6b59bbc65d728fb32c96dd",
                "node_id": "C_kwDOABXSI9oAKGNjOWNlNDdmMGMwMzA3NzJjYTZiNTliYmM2NWQ3MjhmYjMyYzk2ZGQ",
                "commit": {
                        "author": {
                                "name": "Andreas Fernandez",
                                "email": "a.fernandez@scripting-base.de",
                                "date": "2022-07-09T13:38:13Z"
                        },
                        "committer": {
                                "name": "Nikita Hovratov",
                                "email": "nikita.h@live.de",
                                "date": "2022-07-09T21:50:03Z"
                        },
                        "message": "[DOCS] Add missing trailing colons in ReST labels\n\nThe ReST helper tool at https://forger.typo3.com/utilities/rst produced\nfaulty ReST files for quite some time where the trailing colon was\nmissing from labels.\n\nChangelog files affected by this issue are hereby fixed with this\ncommit.\n\nResolves: #97882\nReleases: main\nChange-Id: I96b361b0128680d4bc6434c8ddf1aefc0d280df0\nReviewed-on: https://review.typo3.org/c/Packages/TYPO3.CMS/+/75084\nTested-by: core-ci <typo3@b13.com>\nTested-by: Stefan Bürk <stefan@buerk.tech>\nTested-by: Nikita Hovratov <nikita.h@live.de>\nReviewed-by: Stefan Bürk <stefan@buerk.tech>\nReviewed-by: Nikita Hovratov <nikita.h@live.de>",
                        "tree": {
                                "sha": "fe35b1ec0ae06608ce26e26e966c0277b1a1902a",
                                "url": "https://api.github.com/repos/TYPO3/typo3/git/trees/fe35b1ec0ae06608ce26e26e966c0277b1a1902a"
                        },
                        "url": "https://api.github.com/repos/TYPO3/typo3/git/commits/cc9ce47f0c030772ca6b59bbc65d728fb32c96dd",
                        "comment_count": 0,
                        "verification": {
                                "verified": false,
                                "reason": "unsigned",
                                "signature": null,
                                "payload": null
                        }
                },
                "url": "https://api.github.com/repos/TYPO3/typo3/commits/cc9ce47f0c030772ca6b59bbc65d728fb32c96dd",
                "html_url": "https://github.com/TYPO3/typo3/commit/cc9ce47f0c030772ca6b59bbc65d728fb32c96dd",
                "comments_url": "https://api.github.com/repos/TYPO3/typo3/commits/cc9ce47f0c030772ca6b59bbc65d728fb32c96dd/comments",
                "author": {
                        "login": "andreasfernandez",
                        "id": 1787983,
                        "node_id": "MDQ6VXNlcjE3ODc5ODM=",
                        "avatar_url": "https://avatars.githubusercontent.com/u/1787983?v=4",
                        "gravatar_id": "",
                        "url": "https://api.github.com/users/andreasfernandez",
                        "html_url": "https://github.com/andreasfernandez",
                        "followers_url": "https://api.github.com/users/andreasfernandez/followers",
                        "following_url": "https://api.github.com/users/andreasfernandez/following{/other_user}",
                        "gists_url": "https://api.github.com/users/andreasfernandez/gists{/gist_id}",
                        "starred_url": "https://api.github.com/users/andreasfernandez/starred{/owner}{/repo}",
                        "subscriptions_url": "https://api.github.com/users/andreasfernandez/subscriptions",
                        "organizations_url": "https://api.github.com/users/andreasfernandez/orgs",
                        "repos_url": "https://api.github.com/users/andreasfernandez/repos",
                        "events_url": "https://api.github.com/users/andreasfernandez/events{/privacy}",
                        "received_events_url": "https://api.github.com/users/andreasfernandez/received_events",
                        "type": "User",
                        "site_admin": false
                },
                "committer": {
                        "login": "nhovratov",
                        "id": 19343425,
                        "node_id": "MDQ6VXNlcjE5MzQzNDI1",
                        "avatar_url": "https://avatars.githubusercontent.com/u/19343425?v=4",
                        "gravatar_id": "",
                        "url": "https://api.github.com/users/nhovratov",
                        "html_url": "https://github.com/nhovratov",
                        "followers_url": "https://api.github.com/users/nhovratov/followers",
                        "following_url": "https://api.github.com/users/nhovratov/following{/other_user}",
                        "gists_url": "https://api.github.com/users/nhovratov/gists{/gist_id}",
                        "starred_url": "https://api.github.com/users/nhovratov/starred{/owner}{/repo}",
                        "subscriptions_url": "https://api.github.com/users/nhovratov/subscriptions",
                        "organizations_url": "https://api.github.com/users/nhovratov/orgs",
                        "repos_url": "https://api.github.com/users/nhovratov/repos",
                        "events_url": "https://api.github.com/users/nhovratov/events{/privacy}",
                        "received_events_url": "https://api.github.com/users/nhovratov/received_events",
                        "type": "User",
                        "site_admin": false
                },
                "parents": [
                        {
                                "sha": "5e1f39fddde426a6c444c8f2c9ae4a74e56860b3",
                                "url": "https://api.github.com/repos/TYPO3/typo3/commits/5e1f39fddde426a6c444c8f2c9ae4a74e56860b3",
                                "html_url": "https://github.com/TYPO3/typo3/commit/5e1f39fddde426a6c444c8f2c9ae4a74e56860b3"
                        }
                ]
        },
        "merge_base_commit": {
                "sha": "cc9ce47f0c030772ca6b59bbc65d728fb32c96dd",
                "node_id": "C_kwDOABXSI9oAKGNjOWNlNDdmMGMwMzA3NzJjYTZiNTliYmM2NWQ3MjhmYjMyYzk2ZGQ",
                "commit": {
                        "author": {
                                "name": "Andreas Fernandez",
                                "email": "a.fernandez@scripting-base.de",
                                "date": "2022-07-09T13:38:13Z"
                        },
                        "committer": {
                                "name": "Nikita Hovratov",
                                "email": "nikita.h@live.de",
                                "date": "2022-07-09T21:50:03Z"
                        },
                        "message": "[DOCS] Add missing trailing colons in ReST labels\n\nThe ReST helper tool at https://forger.typo3.com/utilities/rst produced\nfaulty ReST files for quite some time where the trailing colon was\nmissing from labels.\n\nChangelog files affected by this issue are hereby fixed with this\ncommit.\n\nResolves: #97882\nReleases: main\nChange-Id: I96b361b0128680d4bc6434c8ddf1aefc0d280df0\nReviewed-on: https://review.typo3.org/c/Packages/TYPO3.CMS/+/75084\nTested-by: core-ci <typo3@b13.com>\nTested-by: Stefan Bürk <stefan@buerk.tech>\nTested-by: Nikita Hovratov <nikita.h@live.de>\nReviewed-by: Stefan Bürk <stefan@buerk.tech>\nReviewed-by: Nikita Hovratov <nikita.h@live.de>",
                        "tree": {
                                "sha": "fe35b1ec0ae06608ce26e26e966c0277b1a1902a",
                                "url": "https://api.github.com/repos/TYPO3/typo3/git/trees/fe35b1ec0ae06608ce26e26e966c0277b1a1902a"
                        },
                        "url": "https://api.github.com/repos/TYPO3/typo3/git/commits/cc9ce47f0c030772ca6b59bbc65d728fb32c96dd",
                        "comment_count": 0,
                        "verification": {
                                "verified": false,
                                "reason": "unsigned",
                                "signature": null,
                                "payload": null
                        }
                },
                "url": "https://api.github.com/repos/TYPO3/typo3/commits/cc9ce47f0c030772ca6b59bbc65d728fb32c96dd",
                "html_url": "https://github.com/TYPO3/typo3/commit/cc9ce47f0c030772ca6b59bbc65d728fb32c96dd",
                "comments_url": "https://api.github.com/repos/TYPO3/typo3/commits/cc9ce47f0c030772ca6b59bbc65d728fb32c96dd/comments",
                "author": {
                        "login": "andreasfernandez",
                        "id": 1787983,
                        "node_id": "MDQ6VXNlcjE3ODc5ODM=",
                        "avatar_url": "https://avatars.githubusercontent.com/u/1787983?v=4",
                        "gravatar_id": "",
                        "url": "https://api.github.com/users/andreasfernandez",
                        "html_url": "https://github.com/andreasfernandez",
                        "followers_url": "https://api.github.com/users/andreasfernandez/followers",
                        "following_url": "https://api.github.com/users/andreasfernandez/following{/other_user}",
                        "gists_url": "https://api.github.com/users/andreasfernandez/gists{/gist_id}",
                        "starred_url": "https://api.github.com/users/andreasfernandez/starred{/owner}{/repo}",
                        "subscriptions_url": "https://api.github.com/users/andreasfernandez/subscriptions",
                        "organizations_url": "https://api.github.com/users/andreasfernandez/orgs",
                        "repos_url": "https://api.github.com/users/andreasfernandez/repos",
                        "events_url": "https://api.github.com/users/andreasfernandez/events{/privacy}",
                        "received_events_url": "https://api.github.com/users/andreasfernandez/received_events",
                        "type": "User",
                        "site_admin": false
                },
                "committer": {
                        "login": "nhovratov",
                        "id": 19343425,
                        "node_id": "MDQ6VXNlcjE5MzQzNDI1",
                        "avatar_url": "https://avatars.githubusercontent.com/u/19343425?v=4",
                        "gravatar_id": "",
                        "url": "https://api.github.com/users/nhovratov",
                        "html_url": "https://github.com/nhovratov",
                        "followers_url": "https://api.github.com/users/nhovratov/followers",
                        "following_url": "https://api.github.com/users/nhovratov/following{/other_user}",
                        "gists_url": "https://api.github.com/users/nhovratov/gists{/gist_id}",
                        "starred_url": "https://api.github.com/users/nhovratov/starred{/owner}{/repo}",
                        "subscriptions_url": "https://api.github.com/users/nhovratov/subscriptions",
                        "organizations_url": "https://api.github.com/users/nhovratov/orgs",
                        "repos_url": "https://api.github.com/users/nhovratov/repos",
                        "events_url": "https://api.github.com/users/nhovratov/events{/privacy}",
                        "received_events_url": "https://api.github.com/users/nhovratov/received_events",
                        "type": "User",
                        "site_admin": false
                },
                "parents": [
                        {
                                "sha": "5e1f39fddde426a6c444c8f2c9ae4a74e56860b3",
                                "url": "https://api.github.com/repos/TYPO3/typo3/commits/5e1f39fddde426a6c444c8f2c9ae4a74e56860b3",
                                "html_url": "https://github.com/TYPO3/typo3/commit/5e1f39fddde426a6c444c8f2c9ae4a74e56860b3"
                        }
                ]
        },
        "status": "ahead",
        "ahead_by": 1,
        "behind_by": 0,
        "total_commits": 1,
        "commits": [
                {
                        "sha": "47520511f4947a6ebd139a84e831a062a5b61c31",
                        "node_id": "C_kwDOABXSI9oAKDQ3NTIwNTExZjQ5NDdhNmViZDEzOWE4NGU4MzFhMDYyYTViNjFjMzE",
                        "commit": {
                                "author": {
                                        "name": "linawolf",
                                        "email": "112@linawolf.de",
                                        "date": "2022-07-09T11:19:50Z"
                                },
                                "committer": {
                                        "name": "Lina Wolf",
                                        "email": "112@linawolf.de",
                                        "date": "2022-07-10T07:44:08Z"
                                },
                                "message": "[DOCS] Improve changelog for #97454 - new LinkHandler events\n\n- mention the methods of the removed hook to make it easier\n  for developers to search for them.\n- add missing description of the ModifyAllowedItemsEvent\n\nReleases: main\nResolves: #97881\nRelated: #97454\nChange-Id: I3667514d8d6b6ef1865e161d77ce18ec2999cca8\nReviewed-on: https://review.typo3.org/c/Packages/TYPO3.CMS/+/75081\nTested-by: Nikita Hovratov <nikita.h@live.de>\nTested-by: core-ci <typo3@b13.com>\nTested-by: Stefan Bürk <stefan@buerk.tech>\nTested-by: Lina Wolf <112@linawolf.de>\nReviewed-by: Nikita Hovratov <nikita.h@live.de>\nReviewed-by: Stefan Bürk <stefan@buerk.tech>\nReviewed-by: Lina Wolf <112@linawolf.de>",
                                "tree": {
                                        "sha": "1e38a89ef39cdacfef0cd0a3223704bd7ddbabaf",
                                        "url": "https://api.github.com/repos/TYPO3/typo3/git/trees/1e38a89ef39cdacfef0cd0a3223704bd7ddbabaf"
                                },
                                "url": "https://api.github.com/repos/TYPO3/typo3/git/commits/47520511f4947a6ebd139a84e831a062a5b61c31",
                                "comment_count": 0,
                                "verification": {
                                        "verified": false,
                                        "reason": "unsigned",
                                        "signature": null,
                                        "payload": null
                                }
                        },
                        "url": "https://api.github.com/repos/TYPO3/typo3/commits/47520511f4947a6ebd139a84e831a062a5b61c31",
                        "html_url": "https://github.com/TYPO3/typo3/commit/47520511f4947a6ebd139a84e831a062a5b61c31",
                        "comments_url": "https://api.github.com/repos/TYPO3/typo3/commits/47520511f4947a6ebd139a84e831a062a5b61c31/comments",
                        "author": {
                                "login": "linawolf",
                                "id": 48202465,
                                "node_id": "MDQ6VXNlcjQ4MjAyNDY1",
                                "avatar_url": "https://avatars.githubusercontent.com/u/48202465?v=4",
                                "gravatar_id": "",
                                "url": "https://api.github.com/users/linawolf",
                                "html_url": "https://github.com/linawolf",
                                "followers_url": "https://api.github.com/users/linawolf/followers",
                                "following_url": "https://api.github.com/users/linawolf/following{/other_user}",
                                "gists_url": "https://api.github.com/users/linawolf/gists{/gist_id}",
                                "starred_url": "https://api.github.com/users/linawolf/starred{/owner}{/repo}",
                                "subscriptions_url": "https://api.github.com/users/linawolf/subscriptions",
                                "organizations_url": "https://api.github.com/users/linawolf/orgs",
                                "repos_url": "https://api.github.com/users/linawolf/repos",
                                "events_url": "https://api.github.com/users/linawolf/events{/privacy}",
                                "received_events_url": "https://api.github.com/users/linawolf/received_events",
                                "type": "User",
                                "site_admin": false
                        },
                        "committer": {
                                "login": "linawolf",
                                "id": 48202465,
                                "node_id": "MDQ6VXNlcjQ4MjAyNDY1",
                                "avatar_url": "https://avatars.githubusercontent.com/u/48202465?v=4",
                                "gravatar_id": "",
                                "url": "https://api.github.com/users/linawolf",
                                "html_url": "https://github.com/linawolf",
                                "followers_url": "https://api.github.com/users/linawolf/followers",
                                "following_url": "https://api.github.com/users/linawolf/following{/other_user}",
                                "gists_url": "https://api.github.com/users/linawolf/gists{/gist_id}",
                                "starred_url": "https://api.github.com/users/linawolf/starred{/owner}{/repo}",
                                "subscriptions_url": "https://api.github.com/users/linawolf/subscriptions",
                                "organizations_url": "https://api.github.com/users/linawolf/orgs",
                                "repos_url": "https://api.github.com/users/linawolf/repos",
                                "events_url": "https://api.github.com/users/linawolf/events{/privacy}",
                                "received_events_url": "https://api.github.com/users/linawolf/received_events",
                                "type": "User",
                                "site_admin": false
                        },
                        "parents": [
                                {
                                        "sha": "cc9ce47f0c030772ca6b59bbc65d728fb32c96dd",
                                        "url": "https://api.github.com/repos/TYPO3/typo3/commits/cc9ce47f0c030772ca6b59bbc65d728fb32c96dd",
                                        "html_url": "https://github.com/TYPO3/typo3/commit/cc9ce47f0c030772ca6b59bbc65d728fb32c96dd"
                                }
                        ]
                }
        ],
        "files": [
                {
                        "sha": "ff94c8d4dd42a236e0f3f8bb4cfbedd0628026e1",
                        "filename": "typo3/sysext/core/Documentation/Changelog/12.0/Breaking-97454-RemoveLinkBrowserHooks.rst",
                        "status": "modified",
                        "additions": 12,
                        "deletions": 2,
                        "changes": 14,
                        "blob_url": "https://github.com/TYPO3/typo3/blob/47520511f4947a6ebd139a84e831a062a5b61c31/typo3%2Fsysext%2Fcore%2FDocumentation%2FChangelog%2F12.0%2FBreaking-97454-RemoveLinkBrowserHooks.rst",
                        "raw_url": "https://github.com/TYPO3/typo3/raw/47520511f4947a6ebd139a84e831a062a5b61c31/typo3%2Fsysext%2Fcore%2FDocumentation%2FChangelog%2F12.0%2FBreaking-97454-RemoveLinkBrowserHooks.rst",
                        "contents_url": "https://api.github.com/repos/TYPO3/typo3/contents/typo3%2Fsysext%2Fcore%2FDocumentation%2FChangelog%2F12.0%2FBreaking-97454-RemoveLinkBrowserHooks.rst?ref=47520511f4947a6ebd139a84e831a062a5b61c31",
                        "patch": "@@ -1,5 +1,7 @@\n .. include:: /Includes.rst.txt\n\n+.. _breaking-97454-1657327622:\n+\n =============================================\n Breaking: #97454 - Removed Link Browser hooks\n =============================================\n@@ -9,10 +11,18 @@ See :issue:`97454`\n Description\n ===========\n\n-The hooks array :php:`$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'LinkBrowser\'][\'hooks\']` has been\n-removed in favor of new PSR-14 Events :php:`\\\\TYPO3\\\\CMS\\\\Recordlist\\\\Event\\\\ModifyLinkHandlersEvent`\n+The hook :php:`$GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'LinkBrowser\'][\'hooks\']`\n+with its two functions :php:`modifyLinkHandlers()` and\n+:php:`modifyAllowedItems()` has been removed in favor of two new PSR-14 Events\n+:php:`\\\\TYPO3\\\\CMS\\\\Recordlist\\\\Event\\\\ModifyLinkHandlersEvent`\n and :php:`\\\\TYPO3\\\\CMS\\\\Recordlist\\\\Event\\\\ModifyAllowedItemsEvent`.\n\n+.. seealso::\n+    *   :ref:`feature-97454-1657327622`\n+    *   :ref:`t3coreapi:modifyLinkHandlers`\n+    *   :ref:`t3coreapi:ModifyLinkHandlersEvent`\n+    *   :ref:`t3coreapi:ModifyAllowedItemsEvent`\n+\n Impact\n ======\n"
                }
        ]
    }'
);
