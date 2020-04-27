<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Client\GraylogClient;
use App\Extractor\GraylogLogEntry;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use Psr\Log\LoggerInterface;

/**
 * Get various log messages
 */
class GraylogService
{
    private GraylogClient $client;

    private LoggerInterface $logger;

    /**
     * @param GraylogClient $client
     */
    public function __construct(GraylogClient $client, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->logger = $logger;
    }

    /**
     * Get a list of graylog bamboo trigger calls and gerrit votes
     *
     * @return GraylogLogEntry[]
     */
    public function getRecentBambooTriggersAndVotes(): array
    {
        return $this->getLogs(
            'application:intercept AND level:6 AND env:' . getenv('GRAYLOG_ENV')
            . ' AND (ctxt_type:triggerBamboo OR ctxt_type:voteGerrit OR ctxt_type:rebuildNightly OR ctxt_type:reportBrokenNightly)'
            . ' AND ctxt_isSecurity:0'
        );
    }

    /**
     * Get a list of graylog bamboo trigger calls and gerrit votes of core security builds
     *
     * @return GraylogLogEntry[]
     */
    public function getRecentBambooCoreSecurityTriggersAndVotes(): array
    {
        return $this->getLogs(
            'application:intercept AND level:6 AND env:' . getenv('GRAYLOG_ENV')
            . ' AND (ctxt_type:triggerBamboo OR ctxt_type:voteGerrit OR ctxt_type:rebuildNightly OR ctxt_type:reportBrokenNightly)'
            . ' AND ctxt_isSecurity:1'
        );
    }

    /**
     * Log entries related to documentation rendering via bamboo to (new)
     * docs server.
     *
     * @return GraylogLogEntry[]
     */
    public function getRecentBambooDocsActions(): array
    {
        return $this->getLogs(
            'application:intercept AND env:' . getenv('GRAYLOG_ENV')
            . ' AND (ctxt_type:docsRendering)'
        );
    }

    /**
     * Log entries related to documentation redirects via bamboo to (new)
     * docs server.
     *
     * @return GraylogLogEntry[]
     */
    public function getRecentRedirectActions(): array
    {
        return $this->getLogs(
            'application:intercept AND env:' . getenv('GRAYLOG_ENV')
            . ' AND (ctxt_type:docsRedirect)'
        );
    }

    /**
     * Log entries related to documentation third party packages rendering tasks.
     * Shown on documentation site in web interface.
     *
     * @return GraylogLogEntry[]
     */
    public function getRecentBambooDocsThirdPartyTriggers(): array
    {
        return $this->getLogs(
            'application:intercept AND level:6 AND env:' . getenv('GRAYLOG_ENV')
            . ' AND (ctxt_type:triggerBambooDocsThirdParty)'
        );
    }

    /**
     * Returns an array of split / tag log entries grouped by job uuid:
     *
     * [
     *  'aUuid' => [
     *      'queueLog' => GraylogLogEntry // Initial 'job has been queued log entry'
     *      'finished' => true // True if "done" status log row found
     *      'timeTaken' => DateInterval // Time diff between job start and finish
     *      'detailLogs' => [  // All other log rows of this job
     *          GraylogLogEntry
     *      ]
     *  ]
     * ]
     *
     * @return array
     */
    public function getRecentSplitActions(): array
    {
        $queueLogs = $this->getLogs(
            'application:intercept AND level:6 AND env:' . getenv('GRAYLOG_ENV')
            . ' AND ctxt_status:queued AND (ctxt_type:patch OR ctxt_type:tag)'
        );
        $splitActions = [];
        foreach ($queueLogs as $queueLog) {
            $splitActions[$queueLog->uuid] = [
                'queueLog' => $queueLog,
                'finished' => false,
                'detailLogs' => [],
            ];
            $detailLogs = $this->getLogs(
                'application:intercept AND level:6 AND env:' . getenv('GRAYLOG_ENV')
                . ' AND !(ctxt_status:queued)'
                . ' AND (ctxt_type:patch OR ctxt_type:tag)'
                . ' AND ctxt_job_uuid:' . $queueLog->uuid,
                500
            );
            foreach ($detailLogs as $detailLog) {
                $splitActions[$queueLog->uuid]['detailLogs'][] = $detailLog;
                if ($detailLog->status === 'done') {
                    $splitActions[$queueLog->uuid]['finished'] = true;
                    $splitActions[$queueLog->uuid]['timeTaken'] = $detailLog->time->diff($queueLog->time);
                }
            }
        }
        return $splitActions;
    }

    /**
     * Execute a graylog query and return log entries
     *
     * @param string $query
     * @param int $limit
     * @return GraylogLogEntry[]
     */
    private function getLogs(string $query, int $limit = 20): array
    {
        $query = urlencode($query);
        try {
            $response = $this->client->get(
                'search/universal/relative?query=' . $query
                . '&range=2592000' // 30 days max
                . '&limit=' . $limit
                . '&sort=' . urlencode('timestamp:desc')
                . '&pretty=true',
                [
                    'auth' => [getenv('GRAYLOG_TOKEN'), 'token'],
                    'headers' => ['accept' => 'application/json'],
                ]
            );
            $content = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $messages = [];
            if (isset($content['messages']) && is_array($content['messages'])) {
                foreach ($content['messages'] as $message) {
                    $messages[] = new GraylogLogEntry($message['message']);
                }
            }
            return $messages;
        } catch (ClientException $e) {
            // Silent fail if graylog is broken
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            return [];
        } catch (ConnectException $e) {
            // Silent fail if graylog is down
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            return [];
        } catch (ServerException $e) {
            // Silent fail if graylog is down
            $this->logger->critical($e->getMessage(), ['exception' => $e]);
            return [];
        }
    }
}
