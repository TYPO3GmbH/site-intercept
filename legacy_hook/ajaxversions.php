<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept-legacy-hook.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

require __DIR__ . '/vendor/autoload.php';

use App\DocumentationVersions;
use App\ResponseEmitter;
use GuzzleHttp\Psr7\ServerRequest;

$request_origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (preg_match('/^http[s]?:\/\/localhost(:\d+)?$|^http[s]?:\/\/[^\/]+\.ddev\.site(:\d+)?$/', $request_origin)) {
    // Allow requests from localhost and all domains ending with .ddev.site
    header("Access-Control-Allow-Origin: $request_origin");
}

$response = (new DocumentationVersions(ServerRequest::fromGlobals()))->getVersions();
new ResponseEmitter($response);
