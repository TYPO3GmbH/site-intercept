<?php

use Symfony\Component\HttpFoundation\Request;

return Request::create(
    '/bamboo',
    'POST',
    [
        'payload' => json_encode([
            'attachments' => [
                'text' => '<https://bamboo.typo3.com/browse/CORE-DR-43|T3G \u203a Apparel \u203a #25> passed. 2 passed. Manual run by <https://bamboo.typo3.com/browse/user/susanne.moog|Susanne Moog>',
            ],
            'username' => 'Bamboo',
        ], JSON_UNESCAPED_SLASHES),
    ]
);
