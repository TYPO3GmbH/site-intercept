<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use Symfony\Component\HttpFoundation\Request;

return Request::create(
    '/githubpr',
    \Symfony\Component\HttpFoundation\Request::METHOD_POST,
    [],
    [],
    [],
    [],
    '{
      "action": "not-opened"
    }'
);
