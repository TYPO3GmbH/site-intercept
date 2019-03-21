<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Monolog\Processor;

use App\Security\User;
use Symfony\Component\Security\Core\Security;

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
        // Additional fields are hand in via services configuration
        foreach ($this->fieldValues as $fieldName => $fieldValue) {
            if (!is_object($fieldValue)) {
                $record['extra'][$fieldName] = $fieldValue;
            } else {
                // One field can be the security object of symfony
                if ($fieldValue instanceof Security) {
                    $user = $fieldValue->getUser();
                    if ($user instanceof User) {
                        // If we have a logged in user, automatically log username and display name
                        $record['extra']['username'] = $user->getUsername();
                        $record['extra']['userDisplayName'] = $user->getDisplayName();
                    }
                }
            }
        }
        return $record;
    }
}
