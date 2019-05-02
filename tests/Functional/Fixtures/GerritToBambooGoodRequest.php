<?php

use Symfony\Component\HttpFoundation\Request;

return Request::create(
    '/gerrit',
    'POST',
    [
        'changeUrl' => 'https://review.typo3.org/58920/',
        'patchset' => '1',
        'branch' => 'master',
        'project' => 'Packages/TYPO3.CMS'
    ]
);