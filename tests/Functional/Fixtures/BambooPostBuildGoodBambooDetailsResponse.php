<?php
use GuzzleHttp\Psr7\Response;

return new Response(
    200,
    [],
    '{
        "expand": "changes,metadata,plan,vcsRevisions,artifacts,comments,labels,jiraIssues,stages",
        "link": {
        "href": "https://bamboo.typo3.com/rest/api/latest/result/CORE-GTC-30266",
        "rel": "self"
        },
        "plan": {
        "shortName": "Core master pre-merge",
        "shortKey": "GTC",
        "type": "chain",
        "enabled": true,
        "link": {
          "href": "https://bamboo.typo3.com/rest/api/latest/plan/CORE-GTC",
          "rel": "self"
        },
        "key": "CORE-GTC",
        "name": "Core - Core master pre-merge",
        "planKey": {
          "key": "CORE-GTC"
        }
        },
        "planName": "Core master pre-merge",
        "projectName": "Core",
        "buildResultKey": "CORE-GTC-30266",
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
        "buildTestSummary": "48484 passed",
        "successfulTestCount": 48484,
        "failedTestCount": 0,
        "quarantinedTestCount": 0,
        "skippedTestCount": 412,
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
        "size": 2,
        "label": [
          {
            "name": "change-58917_"
          },
          {
            "name": "patchset-2"
          }
        ],
        "start-index": 0,
        "max-result": 2
        },
        "jiraIssues": {
        "size": 0,
        "start-index": 0,
        "max-result": 0
        },
        "stages": {
        "size": 3,
        "start-index": 0,
        "max-result": 3
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
        "key": "CORE-GTC-30266",
        "planResultKey": {
        "key": "CORE-GTC-30266",
        "entityKey": {
          "key": "CORE-GTC"
        },
        "resultNumber": 30266
        },
        "state": "Successful",
        "buildState": "Successful",
        "number": 30266,
        "buildNumber": 30266
    }'
);