<?php

use Symfony\Component\HttpFoundation\Request;

return Request::create(
    '/githubpr',
    'POST',
    [],
    [],
    [],
    [],
    '{
      "action": "not-opened",
    }'
);