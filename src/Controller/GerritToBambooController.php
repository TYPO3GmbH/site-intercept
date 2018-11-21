<?php
declare(strict_types = 1);
namespace App\Controller;

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use App\Service\BambooService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Called by core gerrit review system (review.typo3.org) as patchset-created hook.
 * Triggers a new bamboo pre-merge master or pre-merge v8 or similar
 * run that applies the given change and patch set and runs tests.
 */
class GerritToBambooController extends AbstractController
{
    /**
     * @Route("/gerrit", name="gerrit_to_bamboo")
     * @param Request $request
     * @param BambooService $bambooService
     * @return Response
     */
    public function index(Request $request, BambooService $bambooService): Response
    {
        $changeUrl = $request->request->get('changeUrl');
        $patchSet = (int)$request->request->get('patchset');
        $branch = $request->request->get('branch');
        if ($branch === 'master'
            || $branch === 'TYPO3_8-7'
            || $branch === 'TYPO3_7-6'
        ) {
            $bambooService->triggerNewCoreBuild($changeUrl, $patchSet, $branch);
        }
        return Response::create();
    }
}
