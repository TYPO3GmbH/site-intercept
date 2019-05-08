<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept-legacy-hook.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

require __DIR__ . '/vendor/autoload.php';

use App\RequestForwarder;
use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Uri;

(new RequestForwarder(ServerRequest::fromGlobals()))
    ->to(new Uri('https://docs-hook.typo3.org/'));
