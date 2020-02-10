<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

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
