<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Creator;

/**
 * A message send to and taken from rabbit mq containing
 * details for a core git split job.
 */
class RabbitMqCoreSplitMessage implements \JsonSerializable
{
    /**
     * @var string The source branch to split FROM, eg. 'TYPO3_8-7', '9.2', 'master'
     */
    public $sourceBranch;

    /**
     * @var string The target branch to split TO, eg. '8.7', '9.2', 'master'
     */
    public $targetBranch;

    /**
     * Create a rabbit mq core split message.
     *
     * @param string $sourceBranch
     * @param string $targetBranch
     */
    public function __construct(string $sourceBranch, string $targetBranch)
    {
        if (empty($sourceBranch) || empty($targetBranch)) {
            throw new \RuntimeException('Empty source or target branch');
        }
        $this->sourceBranch = $sourceBranch;
        $this->targetBranch = $targetBranch;
    }

    /**
     * Json representation of this class contains relevant details.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'sourceBranch' => $this->sourceBranch,
            'targetBranch' => $this->targetBranch,
        ];
    }
}
