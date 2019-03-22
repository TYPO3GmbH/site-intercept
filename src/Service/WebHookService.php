<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Exception\UnsupportedWebHookRequestException;
use App\Extractor\PushEvent;
use Symfony\Component\HttpFoundation\Request;

/**
 * This class can handle webhooks from different repository providers, supported:
 * - Bitbucket: Push Events
 * - Github: Push Events for branches and tags
 * - Github: Release Events
 * - Gitlab: Push Events for branches and tags
 */
class WebHookService
{
    public function createPushEvent(Request $request): PushEvent
    {
        if ($request->headers->get('X-Event-Key', '') === 'repo:push') {
            return $this->getPushEventFromBitbucket($request);
        }
        if (in_array($request->headers->get('X-Gitlab-Event', ''), ['Push Hook', 'Tag Push Hook'], true)) {
            return $this->getPushEventFromGitlab($request);
        }
        if (in_array($request->headers->get('X-GitHub-Event', ''), ['push', 'release'], true)) {
            return $this->getPushEventFromGithub($request, $request->headers->get('X-GitHub-Event'));
        }

        throw new UnsupportedWebHookRequestException('The given request is not supported and can not be converted into a PushEvent', 1553256930);
    }

    protected function getPushEventFromBitbucket(Request $request): PushEvent
    {
        $payload = json_decode($request->getContent());
        $repositoryUrl = (string)$payload->push->changes[0]->new->target->links->html->href;
        $repositoryUrl = substr($repositoryUrl, 0, strpos($repositoryUrl, '/commits/'));
        $versionString = (string)$payload->push->changes[0]->new->name;
        $urlToComposerFile = str_replace(
            [
            '{projectUrl}',
            '{repoName}',
            '{versionString}',
        ],
            [
            (string)$payload->repository->project->links->html->href,
            (string)$payload->repository->name,
            $versionString
        ],
            '{projectUrl}/repos/{repoName}/raw/composer.json?at=refs%2Fheads%2F{versionString}'
        );
        return new PushEvent($repositoryUrl, $versionString, $urlToComposerFile);
    }

    protected function getPushEventFromGitlab(Request $request): PushEvent
    {
        $payload = json_decode($request->getContent());
        $repositoryUrl = (string)$payload->repository->git_http_url;
        $versionString = str_replace(['refs/tags/', 'refs/heads/'], '', (string)$payload->ref);
        $urlToComposerFile = str_replace(
            [
            '{webUrl}',
            '{versionString}',
        ],
            [
            (string)$payload->project->web_url,
            $versionString
        ],
            '{webUrl}/raw/{versionString}/composer.json'
        );
        return new PushEvent($repositoryUrl, $versionString, $urlToComposerFile);
    }

    protected function getPushEventFromGithub(Request $request, string $eventType): PushEvent
    {
        $payload = json_decode($request->getContent());
        $repositoryUrl = (string)$payload->repository->clone_url;
        $versionString = ($eventType === 'release')
            ? (string)$payload->release->tag_name
            : str_replace(['refs/tags/', 'refs/heads/'], '', (string)$payload->ref);

        $urlToComposerFile = str_replace(
            [
            '{repoFullName}',
            '{versionString}',
        ],
            [
            (string)$payload->repository->full_name,
            $versionString
        ],
            'https://raw.githubusercontent.com/{repoFullName}/{versionString}/composer.json'
        );
        return new PushEvent($repositoryUrl, $versionString, $urlToComposerFile);
    }
}
