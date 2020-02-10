<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use Symfony\Component\HttpFoundation\Request;

return Request::create(
    '/gerrit',
    'POST',
    [
        'changeUrl' => 'https://review.typo3.org/58920/',
        'patchset' => '1',
        // This branch is not handled
        'branch' => 'TYPO3_6-2',
    ]
);
