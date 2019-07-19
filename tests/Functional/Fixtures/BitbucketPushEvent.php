<?php

use Symfony\Component\HttpFoundation\Request;

$json = file_get_contents(__DIR__ . '/BitbucketPushEventPayload.json');
return Request::create(
    '/bitbucketToPackagist?apiToken=dummyToken&username=horst',
    'POST',
    [],
    [],
    [],
    [],
    $json
);
