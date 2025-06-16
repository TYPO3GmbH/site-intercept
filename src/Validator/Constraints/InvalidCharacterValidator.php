<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class InvalidCharacterValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        // custom constraints should ignore null and empty values to allow
        // other constraints (NotBlank, NotNull, etc.) take care of that
        if (null === $value || '' === $value) {
            return;
        }

        foreach ([';', '{', '}'] as $character) {
            if (str_contains((string) $value, $character)) {
                $this->context
                    ->buildViolation('Invalid character found, please remove all "{{ character }}" from value')
                    ->setParameter('{{ character }}', $character)
                    ->addViolation();
            }
        }
    }
}
