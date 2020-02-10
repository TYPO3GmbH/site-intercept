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
        "expand": "changes,metadata,plan,vcsRevisions,artifacts,comments,labels,jiraIssues,stages",
        "link": {
            "href": "https://bamboo.typo3.com/rest/api/latest/result/CORE-DR-42",
            "rel": "self"
        },
        "plan": {
            "shortName": "Documentation rendering",
            "shortKey": "DR",
            "type": "chain",
            "enabled": true,
            "link": {
                "href": "https://bamboo.typo3.com/rest/api/latest/plan/CORE-DR",
                "rel": "self"
            },
            "key": "CORE-DR",
            "name": "Core - Documentation rendering",
            "planKey": {
                "key": "CORE-DR"
            }
        },
        "planName": "Documentation rendering",
        "projectName": "Core",
        "buildResultKey": "CORE-DR-42",
        "lifeCycleState": "Finished",
        "id": 104342325,
        "buildStartedTime": "2018-11-24T17:43:15.141+01:00",
        "prettyBuildStartedTime": "Sat, 24 Nov, 05:43 PM",
        "buildCompletedTime": "2018-11-24T17:53:40.315+01:00",
        "buildCompletedDate": "2018-11-24T17:53:40.315+01:00",
        "prettyBuildCompletedTime": "Sat, 24 Nov, 05:53 PM",
        "buildDurationInSeconds": 625,
        "buildDuration": 625174,
        "buildDurationDescription": "10 minutes",
        "buildRelativeTime": "41 minutes ago",
        "vcsRevisionKey": "5f700cfed0d4ba2e65f0e66c71140983d85253f6",
        "vcsRevisions": {
            "size": 1,
            "start-index": 0,
            "max-result": 1
        },
        "buildTestSummary": "2 passed",
        "successfulTestCount": 2,
        "failedTestCount": 0,
        "quarantinedTestCount": 0,
        "skippedTestCount": 0,
        "continuable": false,
        "onceOff": false,
        "restartable": false,
        "notRunYet": false,
        "finished": true,
        "successful": true,
        "buildReason": "Manual run by <a href=\"https://bamboo.typo3.com/browse/user/wb\">Wallboard User</a>",
        "reasonSummary": "Manual run by <a href=\"https://bamboo.typo3.com/browse/user/wb\">Wallboard User</a>",
        "artifacts": {
            "size": 0,
            "start-index": 0,
            "max-result": 0
        },
        "comments": {
            "size": 0,
            "start-index": 0,
            "max-result": 0
        },
        "labels": {
            "size": 1,
            "label": [
                {
                    "name": "BUILD_INFORMATION_FILE"
                }
            ],
            "start-index": 0,
            "max-result": 1
        },
        "jiraIssues": {
            "size": 0,
            "start-index": 0,
            "max-result": 0
        },
        "stages": {
            "size": 1,
            "start-index": 0,
            "max-result": 1
        },
        "changes": {
            "size": 0,
            "start-index": 0,
            "max-result": 0
        },
        "metadata": {
            "size": 2,
            "start-index": 0,
            "max-result": 2
        },
        "key": "CORE-DR-42",
        "planResultKey": {
            "key": "CORE-DR-42",
            "entityKey": {
                "key": "CORE-DR"
            },
            "resultNumber": 42
        },
        "state": "Successful",
        "buildState": "Successful",
        "number": 42,
        "buildNumber": 42
    }'
);
