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
    }
}
