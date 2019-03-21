<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Monolog\Processor;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\User;

/**
 * Adds key / values to a log record. Used by graylog logging
 * to add application and context information
 */
class AddFieldProcessor
{
    private $fieldValues = [];

    public function __construct(array $fieldValues = [])
    {
        $this->fieldValues = $fieldValues;
    }

    public function __invoke(array $record)
    {
        foreach ($this->fieldValues as $fieldName => $fieldValue) {
            if (!is_object($fieldValue)) {
                $record['extra'][$fieldName] = $fieldValue;
            } else {
                if ($fieldValue instanceof Security) {
                    $user = $fieldValue->getUser();
                    if ($user instanceof User) {
                        $record['extra']['username'] = $user->getUsername();
                    }
                }
            }
        }
        return $record;
    }
}
