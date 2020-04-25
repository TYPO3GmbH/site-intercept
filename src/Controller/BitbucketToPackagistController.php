<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller;

use App\Exception\DoNotCareException;
use App\Extractor\PackagistUpdateRequest;
use App\Service\PackagistService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Intermediate controller for packagist.org updates to fix
 * request payload sent by bitbucket > packagist
 *
 * Packagist requires a certain structure in the payload which bitbucket
 * does not send.
 */
class BitbucketToPackagistController extends AbstractController
{
    private PackagistService $packagistService;

    public function __construct(PackagistService $packagistService)
    {
        $this->packagistService = $packagistService;
    }
    /**
     * Called by bitbucket as webhook to update packagist packages
     * @Route("/bitbucketToPackagist", name="bitbucketToPackagist")
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        if (!$request->query->has('apiToken') || !$request->query->has('username')) {
            return Response::create('Missing apiToken or username in request.', Response::HTTP_BAD_REQUEST);
        }
        try {
            $pushEventInformation = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return Response::create('Could not decode payload.', Response::HTTP_BAD_REQUEST);
        }
        $apiToken = $request->query->get('apiToken');
        $userName = $request->query->get('username');
        try {
            $packagistUpdateRequest = new PackagistUpdateRequest($pushEventInformation, $apiToken, $userName);
            $response = $this->packagistService->sendUpdateRequest($packagistUpdateRequest);
            $stream = $response->getBody();
            $stream->rewind();
        } catch (\InvalidArgumentException $e) {
            return Response::create($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (DoNotCareException $e) {
            return Response::create('Package not known.', Response::HTTP_BAD_REQUEST);
        }
        return Response::create($stream->getContents(), $response->getStatusCode(), $response->getHeaders());
    }
}
