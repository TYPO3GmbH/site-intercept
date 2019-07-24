<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use Symfony\Component\HttpFoundation\Request;

return Request::create(
    '/split',
    'POST',
    [],
    [],
    [],
    [],
    '{
      "ref": "refs/tags/v9.5.2",
      "before": "0000000000000000000000000000000000000000",
      "after": "3053f9000e50d0333310e95024639cfb14150eda",
      "created": true,
      "deleted": false,
      "forced": false
    }'
);
