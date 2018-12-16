<?php
use GuzzleHttp\Psr7\Response;

return new Response(
    200,
    [],
    '{
        "projectName": "Core",
        "planName": "Core master nightly",
        "buildResultKey": "CORE-GTN-585",
        "prettyBuildCompletedTime": "Sat, 24 Nov, 05:53 PM",
        "buildDurationInSeconds": 625,
        "successful": false,
        "buildTestSummary": "48484 passed",
        "buildNumber": 585
    }'
);