<?php
declare(strict_types = 1);

namespace T3G\Intercept\Gerrit;

class CommitMessageCreator
{

    const MAX_CHARS_PER_LINE = 74;
    const LF = "\n";
    const DOUBLE_LF = "\n\n";

    public function create(string $subject, string $body, int $issueNumber)
    {
        $subject = $this->formatSubject($subject);
        $body = $this->formatBody($body);
        $releases = $this->getReleasesLine($body);
        $resolves = 'Resolves: #' . $issueNumber;

        $message = $subject . self::DOUBLE_LF .
                   $body . self::DOUBLE_LF .
                   $releases . self::LF .
                   $resolves;

        return $message;
    }

    /**
     * @param string $body
     * @return string
     */
    protected function getReleasesLine(string $body)
    {
        $release = '';
        if(preg_match('/^Releases\:\s\w+$/m', $body) < 1) {
            $release = 'Releases: master';
        }
        return $release;
    }

    /**
     * @param string $body
     * @return string
     */
    protected function formatBody(string $body)
    {
        return wordwrap($body, self::MAX_CHARS_PER_LINE, "\n", true);
    }

    /**
     * @param string $subject
     * @return string
     */
    protected function formatSubject(string $subject)
    {
        if (preg_match('/^\[.+?\]/', $subject) < 1) {
            $subject = '[TASK] ' . $subject;
        }
        if (strlen($subject) > self::MAX_CHARS_PER_LINE) {
            $subject = substr($subject, 0, self::MAX_CHARS_PER_LINE);
        }
        return $subject;
    }
}