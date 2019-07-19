<?php

use Symfony\Component\HttpFoundation\Request;

return Request::create(
    '/split',
    'POST',
    [],
    [],
    [],
    [],
    json_encode(['ref' => 'refs/heads/bad-branch'])
);