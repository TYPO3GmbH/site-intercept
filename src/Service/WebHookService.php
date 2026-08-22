<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Exception\DocsNoRstChangesException;
use App\Exception\GitBranchDeletedException;
use App\Exception\GithubHookPingException;
use App\Exception\InvalidWebHookPayloadException;
use App\Exception\UnsupportedWebHookRequestException;
use App\Extractor\PushEvent;
use Symfony\Component\HttpFoundation\Request;

/**
 * This class can handle webhooks from different repository providers, supported:
 * - Bitbucket: Push Events
 * - Github: Push Events for branches and tags
 * - Github: Release Events
 * - Gitlab: Push Events for branches and tags
 * - Forgejo / Gitea: Push Events for branches and tags.
 */
class WebHookService
{
    /**
     * Entry method that creates a push event object from an incoming
     * github / gitlab / bitbucket repository hook. Used to trigger documentation
     * rendering.
     *
     * @return PushEvent[]
     *
     * @throws DocsNoRstChangesException
     * @throws GitBranchDeletedException
     * @throws GithubHookPingException
     */
    public function createPushEvent(Request $request): array
    {
        if (in_array($request->headers->get('X-Event-Key', ''), ['repo:push', 'repo:refs_changed'], true)) {
            return $this->getPushEventFromBitbucket($request);
        }
        if (in_array($request->headers->get('X-Gitlab-Event', ''), ['Push Hook', 'Tag Push Hook'], true)) {
            return $this->getPushEventFromGitlab($request);
        }
        // Forgejo and Gitea send X-Gitea-Event and X-GitHub-Event compatibility
        // headers along with their own, so this check must come before the Github one
        if ('push' === $request->headers->get('X-Forgejo-Event', '')
            || 'push' === $request->headers->get('X-Gitea-Event', '')
        ) {
            return $this->getPushEventFromForgejo($request);
        }
        if ('push' === $request->headers->get('X-GitHub-Event', '')) {
            return $this->getPushEventFromGithub($request);
        }
        if ('ping' === $request->headers->get('X-GitHub-Event', '')) {
            $payload = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);
            throw new GithubHookPingException('', 1557838026, null, (string) $payload->repository->html_url);
        }
        throw new UnsupportedWebHookRequestException('The request could not be decoded or is not supported.', 1553256930);
    }

    /**
     * @return PushEvent[]
     */
    private function getPushEventFromBitbucket(Request $request): array
    {
        $payload = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);
        $events = [];
        $versions = [];

        if (isset($payload->push?->changes[0]?->new?->target?->links?->html->href)) {
            // Cloud (Push)
            // Bitbucket sends one hook, even when multiple branches are pushed
            // Here we extract those branches and create a pushevent per branch
            foreach ($payload->push->changes ?? [] as $index => $change) {
                $changeName = (string) ($change->new->name ?? null);
                if ('' === $changeName) {
                    throw new InvalidWebHookPayloadException('.push.changes.' . $index . 'new.name', 1783338255);
                }

                if (in_array($changeName, $versions, true)) {
                    continue;
                }

                $events[] = $this->pushEventFromBitbucketCloudChange($payload, $change, $index);
                $versions[] = $changeName;
            }
        } else {
            // Server (refs_changed)
            // Bitbucket sends one hook, even when multiple branches are pushed
            // Here we extract those brances and create a pushevent per branch
            foreach ($payload->changes ?? [] as $index => $change) {
                $displayId = (string) ($change->ref->displayId ?? null);
                if ('' === $displayId) {
                    throw new InvalidWebHookPayloadException('.push.changes.' . $index . 'ref.displayId', 1783338287);
                }

                if (in_array($displayId, $versions, true)) {
                    continue;
                }

                $events[] = $this->pushEventFromBitbucketServerChange($payload, $change, $index);
                $versions[] = $displayId;
            }
        }

        return $events;
    }

    /**
     * @return PushEvent[]
     */
    private function getPushEventFromGitlab(Request $request): array
    {
        $content = $request->getContent();
        $payload = json_decode($content, false, 512, JSON_THROW_ON_ERROR);
        $repositoryUrl = (string) $payload->repository?->git_http_url;
        if ('' === $repositoryUrl) {
            throw new InvalidWebHookPayloadException('.repository.git_http_url', 1783338330);
        }
        if (!isset($payload->ref)) {
            throw new InvalidWebHookPayloadException('.ref', 1783338442);
        }

        $versionString = str_replace(['refs/tags/', 'refs/heads/'], '', (string) $payload->ref);
        $urlToComposerFile = (new GitRepositoryService())
            ->resolvePublicComposerJsonUrlByPayload($payload, GitRepositoryService::SERVICE_GITLAB);

        return [new PushEvent($repositoryUrl, $versionString, $urlToComposerFile, $content)];
    }

    /**
     * @return PushEvent[]
     *
     * @throws DocsNoRstChangesException
     * @throws GitBranchDeletedException
     */
    private function getPushEventFromGithub(Request $request): array
    {
        $content = $request->getContent();

        return $this->getPushEventFromGithubStylePayload($this->decodePayload($content), $content, GitRepositoryService::SERVICE_GITHUB);
    }

    /**
     * Forgejo and Gitea push payloads follow the Github push payload structure and
     * are handled by the same code, apart from resolving the composer.json url and
     * from telling a deleted ref apart, both of which differ.
     *
     * @return PushEvent[]
     *
     * @throws DocsNoRstChangesException
     * @throws GitBranchDeletedException
     */
    private function getPushEventFromForgejo(Request $request): array
    {
        $content = $request->getContent();
        $payload = $this->decodePayload($content);
        // Forgejo and Gitea have no 'deleted' property. Deleting a tag sends a
        // regular push event whose 'after' is the all zero object id, 40 or 64
        // characters wide depending on the object format of the repository.
        // Deleting a branch sends no push event at all, only a 'delete' event
        if (is_string($payload->after ?? null) && 1 === preg_match('/^(?:0{40}|0{64})$/D', $payload->after)) {
            throw $this->branchDeletedException($payload);
        }

        return $this->getPushEventFromGithubStylePayload($payload, $content, GitRepositoryService::SERVICE_FORGEJO);
    }

    /**
     * Github, Forgejo and Gitea can all be configured to send the hook with content
     * type 'application/x-www-form-urlencoded', the body then is 'payload=<json>'.
     */
    private function decodePayload(string $content): \stdClass
    {
        try {
            $payload = json_decode($content, false, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            // Not json, so the hook is configured to post a form body. Github,
            // Forgejo and Gitea all offer that, and the old legacy hook used it too
            $payload = urldecode($content);
            $payload = substr($payload, 8); // cut off 'payload=', rest should be json, then
            try {
                $payload = json_decode($payload, false, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                throw new UnsupportedWebHookRequestException('The request could not be decoded or is not supported.', 1559152710);
            }
        }
        // Valid json, but not an object, so it can not be a hook payload
        if (!$payload instanceof \stdClass) {
            throw new UnsupportedWebHookRequestException('The request could not be decoded or is not supported.', 1785754800);
        }

        return $payload;
    }

    /**
     * @return PushEvent[]
     *
     * @throws DocsNoRstChangesException
     * @throws GitBranchDeletedException
     */
    private function getPushEventFromGithubStylePayload(\stdClass $payload, string $content, string $repoService): array
    {
        if (!empty($payload->deleted) && true === $payload->deleted) {
            throw $this->branchDeletedException($payload);
        }

        // Only for actual push events, not releases
        if (!empty($payload->commits)) {
            $triggeringChange = false;
            $deliveredCommits = 0;
            foreach ($payload->commits as $commit) {
                ++$deliveredCommits;
                $files = array_merge($commit->added ?? [], $commit->modified ?? [], $commit->removed ?? []);
                foreach ($files as $file) {
                    if ('README.md' === $file
                        || 'README.rst' === $file
                        || str_starts_with((string) $file, 'Documentation/')
                    ) {
                        $triggeringChange = true;
                        break 2;
                    }
                }
            }

            // Senders cap the commit list, Forgejo at 15 and Gitea at 5 by default,
            // while 'total_commits' keeps the real number. A documentation change in
            // one of the dropped commits would be lost for good, so rather render
            // once too often than never. Count what was iterated rather than the
            // payload value, which is not required to be an array
            $isTruncated = (int) ($payload->total_commits ?? 0) > $deliveredCommits;
            if (!$triggeringChange && !$isTruncated) {
                throw new DocsNoRstChangesException(sprintf('The commit %s pushed to %s:%s doesn\'t contain any changed .rst files', $payload->head_commit->id ?? '[unknown]', $payload->repository->full_name ?? '[unknown]', $payload->ref ?? '[unknown]'), 1570011098);
            }
        }

        $repositoryUrl = (string) ($payload->repository->clone_url ?? null);
        if ('' === $repositoryUrl) {
            throw new InvalidWebHookPayloadException('.repository.clone_url', 1783336462);
        }
        if (GitRepositoryService::SERVICE_FORGEJO === $repoService) {
            $this->assertForgejoPayloadIsComplete($payload);
        }
        $versionString = str_replace(['refs/tags/', 'refs/heads/'], '', (string) $payload->ref);
        $urlToComposerFile = (new GitRepositoryService())
            ->resolvePublicComposerJsonUrlByPayload($payload, $repoService);

        return [new PushEvent($repositoryUrl, $versionString, $urlToComposerFile, $content)];
    }

    /**
     * The Forgejo url format needs a full ref to tell a branch from a tag, and the
     * repository html url as its base, so both are required to be usable here.
     */
    private function assertForgejoPayloadIsComplete(\stdClass $payload): void
    {
        $ref = (string) ($payload->ref ?? null);
        if (!str_starts_with($ref, 'refs/heads/') && !str_starts_with($ref, 'refs/tags/')) {
            throw new InvalidWebHookPayloadException('.ref', 1785749100);
        }
        if ('' === str_replace(['refs/tags/', 'refs/heads/'], '', $ref)) {
            throw new InvalidWebHookPayloadException('.ref', 1785749100);
        }
        if ('' === (string) ($payload->repository->html_url ?? null)) {
            throw new InvalidWebHookPayloadException('.repository.html_url', 1785751200);
        }
    }

    private function branchDeletedException(\stdClass $payload): GitBranchDeletedException
    {
        $cloneUrl = $payload->repository->clone_url ?? '';

        return new GitBranchDeletedException(sprintf('Webhook was triggered on deleted branch %s for repository %s.', $payload->ref ?? '[unknown]', $cloneUrl), 1564408696);
    }

    private function pushEventFromBitbucketCloudChange(\stdClass $payload, \stdClass $change, int|string $index): PushEvent
    {
        unset($payload->push->changes);
        $payload->push->changes = [0 => $change];
        $versionString = (string) ($change->new->name ?? null);
        if ('' === $versionString) {
            throw new InvalidWebHookPayloadException('.push.changes.' . $index . '.new.name', 1783337619);
        }

        $repositoryUrl = (string) ($change->new?->target?->links?->html->href ?? null);
        if ('' === $repositoryUrl) {
            throw new InvalidWebHookPayloadException('.push.changes.' . $index . '.new.target.links.html.href', 1783337633);
        }
        // Add .git at end if it misses. This must be aligned, otherwise manual adding of configuration will go wrong.
        if (!str_ends_with($repositoryUrl, '.git')) {
            $repositoryUrl .= '.git';
        }
        if (is_int(strpos($repositoryUrl, '/commits/'))) {
            $repositoryUrl = substr($repositoryUrl, 0, strpos($repositoryUrl, '/commits/'));
        }
        $urlToComposerFile = (new GitRepositoryService())
            ->resolvePublicComposerJsonUrlByPayload($payload, GitRepositoryService::SERVICE_BITBUCKET_CLOUD);

        return new PushEvent($repositoryUrl, $versionString, $urlToComposerFile, json_encode($payload, JSON_THROW_ON_ERROR));
    }

    private function pushEventFromBitbucketServerChange(\stdClass $payload, \stdClass $change, int|string $index): PushEvent
    {
        unset($payload->changes);
        $payload->changes = [0 => $change];
        $versionString = (string) ($change->ref->displayId ?? null);
        if ('' === $versionString) {
            throw new InvalidWebHookPayloadException('.push.changes.' . $index . 'ref.displayId', 1783339961);
        }
        $repositoryUrl = null;
        // Server (refs_changed)
        // In case of self-hosted, Bitbucket provides a git clone url
        // We have to use this url, as html url will not work with git clone
        foreach ($payload->repository?->links->clone ?? [] as $cloneIndex => $cloneInformation) {
            if ('http' === ($cloneInformation->name ?? null)) {
                $repositoryUrl = (string) ($cloneInformation->href ?? null);
                if ('' === $repositoryUrl) {
                    throw new InvalidWebHookPayloadException('.repository.links.clone.' . $cloneIndex . 'href', 1783339966);
                }
                break;
            }
        }

        if (null === $repositoryUrl) {
            throw new UnsupportedWebHookRequestException('Private repositories are not supported.', 1594283381);
        }

        $urlToComposerFile = (new GitRepositoryService())
            ->resolvePublicComposerJsonUrlByPayload($payload, GitRepositoryService::SERVICE_BITBUCKET_SERVER);

        return new PushEvent($repositoryUrl, $versionString, $urlToComposerFile, json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
