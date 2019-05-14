<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller;

use App\Exception\ComposerJsonInvalidException;
use App\Exception\ComposerJsonNotFoundException;
use App\Exception\DocsPackageDoNotCareBranch;
use App\Exception\DocsPackageRegisteredWithDifferentRepositoryException;
use App\Exception\GithubHookPingException;
use App\Exception\UnsupportedWebHookRequestException;
use App\Service\BambooService;
use App\Service\DocumentationBuildInformationService;
use App\Service\WebHookService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Trigger documentation rendering from a repository hook that calls
 * https://docs-hook.typo3.org/ or /docs/ route
 */
class DocsToBambooController extends AbstractController
{
    /**
     * @Route("/docs", name="docs_to_bamboo")
     * @Route("/", host="docs-hook.typo3.org", name="docs_hook_to_bamboo")
     * @param Request $request
     * @param BambooService $bambooService
     * @param WebHookService $webhookService
     * @param DocumentationBuildInformationService $documentationBuildInformationService
     * @param LoggerInterface $logger
     * @return Response
     */
    public function index(
        Request $request,
        BambooService $bambooService,
        WebHookService $webhookService,
        DocumentationBuildInformationService $documentationBuildInformationService,
        LoggerInterface $logger
    ): Response
    {
        try {
            $pushEvent = $webhookService->createPushEvent($request);
            $composerJson = $documentationBuildInformationService->fetchRemoteComposerJson($pushEvent->getUrlToComposerFile());
            $buildInformation = $documentationBuildInformationService->generateBuildInformation($pushEvent, $composerJson);
            $documentationBuildInformationService->assertBuildWasTriggeredByRepositoryOwner($buildInformation);
            $documentationBuildInformationService->dumpDeploymentInformationFile($buildInformation);
            $documentationBuildInformationService->registerDocumentationRendering($buildInformation);
            $bambooBuildTriggered = $bambooService->triggerDocumentationPlan($buildInformation);
            $logger->info(
                'Triggered docs build',
                [
                    'type' => 'docsRendering',
                    'status' => 'triggered',
                    'triggeredBy' => 'api',
                    'repository' => $buildInformation->repositoryUrl,
                    'package' => $buildInformation->packageName,
                    'sourceBranch' => $buildInformation->sourceBranch,
                    'targetBranch' => $buildInformation->targetBranchDirectory,
                    'bambooKey' => $bambooBuildTriggered->buildResultKey,
                ]
            );
            return Response::create();
        } catch (GithubHookPingException $e) {
            // Hook payload is a 'github ping' - log that as "info / success' with the
            // url that hook came from. This is triggered by github when a new web hook is added,
            // we want to be nice and make this one succeed.
            $logger->info(
                'Docs hook ping from github repository ' . $e->getRespositoryUrl(),
                [
                    'type' => 'docsRendering',
                    'status' => 'githubPing',
                    'triggeredBy' => 'api',
                    'repository' => $e->getRespositoryUrl(),
                ]
            );
            return Response::create('Received github ping. Please push content to the repository to render some documentation. See https://intercept.typo3.com for more information.', 200);
        } catch (UnsupportedWebHookRequestException $e) {
            // Hook payload could not be identified as hook that should trigger rendering
            $logger->warning(
                'Can not render documentation: ' . $e->getMessage(),
                [
                    'type' => 'docsRendering',
                    'status' => 'unsupportedHook',
                    'headers' => $request->headers,
                    'payload' => $request->getContent(),
                    'triggeredBy' => 'api',
                    'exceptionCode' => $e->getCode(),
                ]
            );
            // 412: precondition failed
            return Response::create('Invalid hook payload. See https://intercept.typo3.com for more information.', 412);
        } catch (ComposerJsonNotFoundException $e) {
            // Repository did not provide a composer.json, or fetch failed
            $logger->warning(
                'Can not render documentation: The repository at ' . $pushEvent->getRepositoryUrl() . ' MUST have a composer.json file on top level.',
                [
                    'type' => 'docsRendering',
                    'status' => 'noComposerJson',
                    'triggeredBy' => 'api',
                    'exceptionCode' => $e->getCode(),
                    'exceptionMessage' => $e->getMessage(),
                    'repository' => $pushEvent->getRepositoryUrl(),
                    'composerFile' => $pushEvent->getUrlToComposerFile(),
                ]
            );
            return Response::create('No composer.json found, invalid or unable to fetch. See https://intercept.typo3.com for more information.', 412);
        } catch (ComposerJsonInvalidException $e) {
            $logger->warning(
                'Can not render documentation: ' . $e->getMessage(),
                [
                    'type' => 'docsRendering',
                    'status' => 'invalidComposerJson',
                    'triggeredBy' => 'api',
                    'exceptionCode' => $e->getCode(),
                    'exceptionMessage' => $e->getMessage(),
                    'repository' => $pushEvent->getRepositoryUrl(),
                    'composerFile' => $pushEvent->getUrlToComposerFile(),
                ]
            );
            return Response::create('Invalid composer.json. See https://intercept.typo3.com for more information.', 412);
        } catch (DocsPackageRegisteredWithDifferentRepositoryException $e) {
            $logger->warning(
                'Can not render documentation: ' . $e->getMessage(),
                [
                    'type' => 'docsRendering',
                    'status' => 'packageRegisteredWithDifferentRepository',
                    'triggeredBy' => 'api',
                    'exceptionCode' => $e->getCode(),
                    'exceptionMessage' => $e->getMessage(),
                    'repository' => $pushEvent->getRepositoryUrl(),
                    'package' => $buildInformation->packageName,
                ]
            );
            return Response::create('Package already registered for different repository. See https://intercept.typo3.com for more information.', 412);
        } catch (DocsPackageDoNotCareBranch $e) {
            $logger->warning(
                'Can not render documentation: ' . $e->getMessage(),
                [
                    'type' => 'docsRendering',
                    'status' => 'noRelevantBranchOrTag',
                    'triggeredBy' => 'api',
                    'exceptionCode' => $e->getCode(),
                    'exceptionMessage' => $e->getMessage(),
                    'repository' => $pushEvent->getRepositoryUrl(),
                    'package' => $buildInformation->packageName,
                    'sourceBranch' => $pushEvent->getVersionString(),
                ]
            );
            return Response::create('Branch or tag name ignored for decumentation rendering. See https://intercept.typo3.com for more information.', 412);
        }
    }
}
