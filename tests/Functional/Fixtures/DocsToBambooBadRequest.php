<?php

use Symfony\Component\HttpFoundation\Request;

return Request::create(
    '/docs',
    'POST',
    [],
    [],
    [],
    [],
    '
    {
      "ref": "refs/foo/latest"
    }
    '
);