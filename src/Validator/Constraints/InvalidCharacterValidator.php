<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class InvalidCharacterValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        // custom constraints should ignore null and empty values to allow
        // other constraints (NotBlank, NotNull, etc.) take care of that
        if ($value === null || $value === '') {
            return;
        }

        foreach ([';', '{', '}'] as $character) {
            if (strpos($value, $character) !== false) {
                $this->context
                    ->buildViolation('Invalid character found, please remove all "{{ character }}" from value')
                    ->setParameter('{{ character }}', $character)
                    ->addViolation();
            }
        }
    }
}
