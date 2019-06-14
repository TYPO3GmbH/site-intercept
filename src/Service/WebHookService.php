<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Exception\GithubHookPingException;
use App\Exception\UnsupportedWebHookRequestException;
use App\Extractor\PushEvent;
use stdClass;
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
    /**
     * Entry method that creates a push event object from an incoming
     * github / gitlab / bitbucket repository hook. Used to trigger documentation
     * rendering.
     */
    public function createPushEvent(Request $request): array
    {
        if (in_array($request->headers->get('X-Event-Key', ''), ['repo:push', 'repo:refs_changed'], true)) {
            return $this->getPushEventFromBitbucket($request);
        }
        if (in_array($request->headers->get('X-Gitlab-Event', ''), ['Push Hook', 'Tag Push Hook'], true)) {
            return $this->getPushEventFromGitlab($request);
        }
        if (in_array($request->headers->get('X-GitHub-Event', ''), ['push', 'release'], true)) {
            return $this->getPushEventFromGithub($request, $request->headers->get('X-GitHub-Event'));
        }
        if ($request->headers->get('X-GitHub-Event', '') === 'ping') {
            $payload = json_decode($request->getContent(), false);
            throw new GithubHookPingException('', 1557838026, null, (string)$payload->repository->html_url);
        }
        throw new UnsupportedWebHookRequestException('The request could not be decoded or is not supported.', 1553256930);
    }

    protected function getPushEventFromBitbucket(Request $request): array
    {
        $payload = json_decode($request->getContent(), false);
        $events = [];
        if (isset($payload->push->changes[0]->new->target->links->html->href)) {
            // Cloud (Push)
            // Bitbucket sends one hook, even when multiple branches are pushed
            // Here we extract those brances and create a pushevent per branch
            $versions = [];
            foreach ($payload->push->changes as $change) {
                if (in_array((string)$change->new->name, $versions, true)) {
                    continue;
                }

                $events[] = $this->pushEventFromBitbucketCloudChange($payload, $change);
                $versions[] = (string)$change->new->name;
            }
        } else {
            // Server (refs_changed)
            // Bitbucket sends one hook, even when multiple branches are pushed
            // Here we extract those brances and create a pushevent per branch
            $versions = [];
            foreach ($payload->changes as $change) {
                if (in_array((string)$change->ref->displayId, $versions, true)) {
                    continue;
                }

                $events[] = $this->pushEventFromBitbucketServerChange($payload, $change);
                $versions[] = (string)$change->ref->displayId;
            }
        }

        return $events;
    }

    protected function getPushEventFromGitlab(Request $request): array
    {
        $payload = json_decode($request->getContent(), false);
        $repositoryUrl = (string)$payload->repository->git_http_url;
        $versionString = str_replace(['refs/tags/', 'refs/heads/'], '', (string)$payload->ref);
        $urlToComposerFile = (new GitRepositoryService())
            ->resolvePublicComposerJsonUrlByPayload($payload, GitRepositoryService::SERVICE_GITLAB);

        return [new PushEvent($repositoryUrl, $versionString, $urlToComposerFile)];
    }

    protected function getPushEventFromGithub(Request $request, string $eventType): array
    {
        $content = $request->getContent();
        $payload = json_decode($content, false);
        if ($payload === null) {
            // If can't be decoded to json, this might be a x-www-form-encoded body
            // probably by using the old legacy hook, that used this
            $payload = urldecode($content);
            $payload = substr($payload, 8); // cut off 'payload=', rest should be json, then
            $payload = json_decode($payload, false);
        }
        if ($payload === null) {
            throw new UnsupportedWebHookRequestException('The request could not be decoded or is not supported.', 1559152710);
        }

        $repositoryUrl = (string)$payload->repository->clone_url;
        $versionString = ($eventType === 'release')
            ? (string)$payload->release->tag_name
            : str_replace(['refs/tags/', 'refs/heads/'], '', (string)$payload->ref);
        $urlToComposerFile = (new GitRepositoryService())
            ->resolvePublicComposerJsonUrlByPayload($payload, GitRepositoryService::SERVICE_GITHUB, $eventType);

        return [new PushEvent($repositoryUrl, $versionString, $urlToComposerFile)];
    }

    protected function pushEventFromBitbucketCloudChange(stdClass $payload, stdClass $change): PushEvent
    {
        unset($payload->push->changes);
        $payload->push->changes = [0 => $change];
        $versionString = (string)$change->new->name;
        $repositoryUrl = (string)$change->new->target->links->html->href;
        // Add .git at end if it misses. This must be aligned, otherwise manual adding of configuration will go wrong.
        if (substr($repositoryUrl, -4) !== '.git') {
            $repositoryUrl = $repositoryUrl . '.git';
        }
        if (is_int(strpos($repositoryUrl, '/commits/'))) {
            $repositoryUrl = substr($repositoryUrl, 0, strpos($repositoryUrl, '/commits/'));
        }
        $urlToComposerFile = (new GitRepositoryService())
            ->resolvePublicComposerJsonUrlByPayload($payload, GitRepositoryService::SERVICE_BITBUCKET_CLOUD);

        return new PushEvent($repositoryUrl, $versionString, $urlToComposerFile);
    }

    protected function pushEventFromBitbucketServerChange(stdClass $payload, stdClass $change): PushEvent
    {
        unset($payload->changes);
        $payload->changes = [0 => $change];
        $versionString = (string)$change->ref->displayId;
        // Server (refs_changed)
        // In case of self hosted, Bitbucket provides a git clone url
        // We have to use this url, as html url will not work with git clone
        foreach ($payload->repository->links->clone as $cloneInformation) {
            if ($cloneInformation->name === 'http') {
                $repositoryUrl = (string)$cloneInformation->href;
                break;
            }
        }
        $urlToComposerFile = (new GitRepositoryService())
            ->resolvePublicComposerJsonUrlByPayload($payload, GitRepositoryService::SERVICE_BITBUCKET_SERVER);

        return new PushEvent($repositoryUrl, $versionString, $urlToComposerFile);
    }
}
