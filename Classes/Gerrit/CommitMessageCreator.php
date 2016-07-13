<?php
declare(strict_types = 1);

namespace T3G\Intercept\Gerrit;

class CommitMessageCreator
{

    public function create(string $subject, string $body, int $issueNumber)
    {
        return
<<<MESSAGE
[TASK] $subject

$body

Releases: master
Resolves: #$issueNumber
MESSAGE;
    }
}