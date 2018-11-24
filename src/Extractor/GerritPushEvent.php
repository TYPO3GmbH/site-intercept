<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Extractor;

use Symfony\Component\HttpFoundation\Request;

/**
 * Extract information from a gerrit push event hook
 * needed to trigger a bamboo pre-merge build
 */
class GerritPushEvent
{
    /**
     * @var string The change url, eg. 'https://review.typo3.org/48574/'
     */
    public $changeUrl;

    /**
     * @var int The patch set, eg. '5'
     */
    public $patchSet;

    /**
     * @var string The affected branch, eg. 'master' or 'TYPO3_8-7'
     */
    public $branch;

    /**
     * Extract information needed from a gerrit push event hook
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->changeUrl = $request->request->get('changeUrl');
        $this->patchSet = (int)$request->request->get('patchset');
        $this->branch = $request->request->get('branch');
    }
}
