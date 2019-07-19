<?php

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
