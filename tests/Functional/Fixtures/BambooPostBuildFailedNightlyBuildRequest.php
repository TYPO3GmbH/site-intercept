<?php

use Symfony\Component\HttpFoundation\Request;

return Request::create(
    '/bamboo',
    'POST',
    [
        'payload' => json_encode([
            'attachments' => [
                [
                    'text' => '<https://bamboo.typo3.com/browse/CORE-GTN-585|Core › Core nightly master › #585> failed.',
                ]
            ]
        ], JSON_UNESCAPED_SLASHES),
    ]
);