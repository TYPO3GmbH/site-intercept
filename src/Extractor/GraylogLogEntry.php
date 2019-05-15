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
    public $type;

    /**
     * @var \DateTime
     */
    public $time;

    /**
     * @var string environment, eg. 'prod'
     */
    public $env;

    /**
     * @var string Plain message string, eg. 'Triggered bamboo core build "CORE-GTC-30744" for change "57609" with patch set "11" on branch "master".`
     */
    public $message;

    /**
     * @var int Log level, eg. '6'
     */
    public $level;

    /**
     * @var string 'interface' if log entry has been triggered by users calling web interface, 'api' otherwise
     */
    public $triggeredBy;

    /**
     * @var string Optional action that triggered the invocation
     */
    public $subType;

    /**
     * @var string Optionally set for specific types
     */
    public $branch;

    /**
     * @var int Optionally set for specific types
     */
    public $change;

    /**
     * @var int Optionally set for specific types
     */
    public $patch;

    /**
     * @var string Optionally set if this has been a 'triggerBamboo' log entry
     */
    public $bambooKey;

    /**
     * @var bool Optionally set if this has been a 'vote on gerrit' log entry
     */
    public $vote;

    /**
     * @var string Optionally set for core split/tag jobs
     */
    public $uuid;

    /**
     * @var string Optionally set for core split/tag jobs, also used in docs rendering
     */
    public $status;

    /**
     * @var string Optionally set for core split/tag jobs, also used in docs rendering
     */
    public $sourceBranch;

    /**
     * @var string Optionally set for core split/tag jobs, also used in docs rendering
     */
    public $targetBranch;

    /**
     * @var string Optionally set for core split/tag jobs
     */
    public $tag;

    /**
     * @var string Optional LDAP user name that triggered something
     */
    public $username;

    /**
     * @var string Optional LDAP display name that triggered something
     */
    public $userDisplayName;

    /**
     * @var string Optional 'exception code', used in docs rendering, timestamp
     */
    public $exceptionCode;

    /**
     * @var string Optional 'repository url', used in docs rendering
     */
    public $repository;

    /**
     * @var string Optional 'composer file url', used in docs rendering
     */
    public $composerFile;

    /**
     * @var string Optional package name, used in docs rendering, eg. 'lolli42/enetcache'
     */
    public $package;

    /**
     * @var array Optional redirect data
     */
    public $redirect;

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
        if (!isset($entry['ctxt_type']) || !isset($entry['level']) || !isset($entry['env']) || !isset($entry['message']) || !isset($entry['timestamp'])) {
            throw new \RuntimeException('Need env, level, ctxt_type, message, timestamp to parse');
        }
        if (isset($entry['ctxt_triggeredBy']) && ($entry['ctxt_triggeredBy'] !== 'api' && $entry['ctxt_triggeredBy'] !== 'interface')) {
            throw new \RuntimeException('ctxt_triggeredBy must be either "api" or "interface", ' . $entry['ctxt_triggeredBy'] . ' given.');
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

        $this->redirect = json_decode($entry['ctxt_redirect'] ?? '{}', true);
    }
}
