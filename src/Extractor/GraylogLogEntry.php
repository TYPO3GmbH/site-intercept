<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Extractor;

/**
 * Class represents a graylog log entry
 */
class GraylogLogEntry
{
    /**
     * @var string Log message type, eg. 'triggerBamboo'
     */
    public string $type;

    public \DateTime $time;

    /**
     * @var string environment, eg. 'prod'
     */
    public string $env;

    /**
     * @var string Plain message string, eg. 'Triggered bamboo core build "CORE-GTC-30744" for change "57609" with patch set "11" on branch "master".`
     */
    public string $message;

    /**
     * @var int Log level, eg. '6'
     */
    public int $level;

    /**
     * @var string 'interface' if log entry has been triggered by users calling web interface, 'api' otherwise
     */
    public string $triggeredBy;

    /**
     * @var string Optional action that triggered the invocation
     */
    public string $subType;

    /**
     * @var string Optionally set for specific types
     */
    public string $branch;

    /**
     * @var int Optionally set for specific types
     */
    public int $change;

    /**
     * @var int Optionally set for specific types
     */
    public int $patch;

    /**
     * @var string Optionally set if this has been a 'triggerBamboo' log entry
     */
    public string $bambooKey;

    /**
     * @var bool Optionally set if this has been a 'vote on gerrit' log entry
     */
    public bool $vote;

    /**
     * @var string Optionally set for core split/tag jobs
     */
    public string $uuid;

    /**
     * @var string Optionally set for core split/tag jobs, also used in docs rendering
     */
    public string $status;

    /**
     * @var string Optionally set for core split/tag jobs, also used in docs rendering
     */
    public string $sourceBranch;

    /**
     * @var string Optionally set for core split/tag jobs, also used in docs rendering
     */
    public string $targetBranch;

    /**
     * @var string Optionally set for core split/tag jobs
     */
    public string $tag;

    /**
     * @var string Optional LDAP user name that triggered something
     */
    public string $username;

    /**
     * @var string Optional LDAP display name that triggered something
     */
    public string $userDisplayName;

    /**
     * @var string Optional 'exception code', used in docs rendering, timestamp
     */
    public string $exceptionCode;

    /**
     * @var string Optional 'repository url', used in docs rendering
     */
    public string $repository;

    /**
     * @var string Optional 'composer file url', used in docs rendering
     */
    public string $composerFile;

    /**
     * @var string Optional package name, used in docs rendering, eg. 'lolli42/enetcache'
     */
    public string $package;

    /**
     * @var array Optional redirect data
     */
    public array $redirect;

    /**
     * Extract information from a graylog log entry
     *
     * @param array $entry
     */
    public function __construct(array $entry)
    {
        if (!isset($entry['application']) || $entry['application'] !== 'intercept') {
            throw new \RuntimeException('Will not parse a non-intercept log entry');
        }
        if (!isset($entry['ctxt_type'], $entry['level'], $entry['env'], $entry['message'], $entry['timestamp'])) {
            throw new \RuntimeException('Need env, level, ctxt_type, message, timestamp to parse');
        }
        if (
            isset($entry['ctxt_triggeredBy'])
            && (!in_array($entry['ctxt_triggeredBy'], ['api', 'interface', 'CLI'], true))
        ) {
            throw new \RuntimeException('ctxt_triggeredBy must be either "api" or "interface" or "CLI", ' . $entry['ctxt_triggeredBy'] . ' given.');
        }
        $this->type = $entry['ctxt_type'];
        $this->subType = $entry['ctxt_subType'] ?? '';
        $this->time = new \DateTime($entry['timestamp']);
        $this->env = $entry['env'];
        $this->level = (int)$entry['level'];
        $this->message = $entry['message'];

        $this->branch = $entry['ctxt_branch'] ?? '';
        $this->change = (int)($entry['ctxt_change'] ?? 0);
        $this->patch = (int)($entry['ctxt_patch'] ?? 0);
        $this->bambooKey = $entry['ctxt_bambooKey'] ?? '';
        $this->vote = ($entry['ctxt_vote'] ?? '');
        $this->triggeredBy = $entry['ctxt_triggeredBy'] ?? '';
        $this->uuid = $entry['ctxt_job_uuid'] ?? '';
        $this->status = $entry['ctxt_status'] ?? '';
        $this->sourceBranch = $entry['ctxt_sourceBranch'] ?? '';
        $this->targetBranch = $entry['ctxt_targetBranch'] ?? '';
        $this->tag = $entry['ctxt_tag'] ?? '';

        $this->username = $entry['username'] ?? '';
        $this->userDisplayName = $entry['userDisplayName'] ?? '';

        $this->exceptionCode = $entry['ctxt_exceptionCode'] ?? '';
        $this->repository = $entry['ctxt_repository'] ?? '';
        $this->composerFile = $entry['ctxt_composerFile'] ?? '';
        $this->package = $entry['ctxt_package'] ?? '';

        $this->redirect = json_decode($entry['ctxt_redirect'] ?? '{}', true, 512, JSON_THROW_ON_ERROR);
    }
}
