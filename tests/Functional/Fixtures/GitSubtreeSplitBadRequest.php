<?php

use Symfony\Component\HttpFoundation\Request;

return Request::create(
    '/split',
    'POST',
    [],
    [],
    [],
    [],
    '{
      "ref": "refs/heads/bad-branch",
    }'
);