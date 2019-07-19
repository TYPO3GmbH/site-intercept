<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

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
