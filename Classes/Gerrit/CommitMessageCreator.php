<?php
declare(strict_types = 1);

namespace T3G\Intercept\Gerrit;

class CommitMessageCreator
{

    const MAX_CHARS_PER_LINE = 74;
    const DOUBLE_LF = "\n\n";

    public function create(string $subject, string $body, int $issueNumber)
    {
        $subject = $this->formatSubject($subject);
        $body = $this->formatBody($body);
        $releases = 'Releases: master';
        $resolves = 'Resolves: #' . $issueNumber;

        $message = $subject . self::DOUBLE_LF .
                   $body . self::DOUBLE_LF .
                   $releases . self::DOUBLE_LF .
                   $resolves;

        return $message;
    }

    /**
     * @param string $body
     * @return string
     */
    protected function formatBody(string $body)
    {
        $lines = explode("\n", $body);
        $formattedBody = '';
        foreach ($lines as $line) {
            if (strlen($line) > self::MAX_CHARS_PER_LINE) {
                $chunks = str_split($line, self::MAX_CHARS_PER_LINE);
                $formattedBody .= implode("\n", $chunks);
            } else {
                $formattedBody .= $line;
            }
            $formattedBody .= "\n";
        }
        return $formattedBody;
    }

    /**
     * @param string $subject
     * @return string
     */
    protected function formatSubject(string $subject)
    {
        $subject = '[TASK] ' . $subject;
        if (strlen($subject) > self::MAX_CHARS_PER_LINE) {
            $subject = substr($subject, 0, self::MAX_CHARS_PER_LINE);
        }
        return $subject;
    }
}