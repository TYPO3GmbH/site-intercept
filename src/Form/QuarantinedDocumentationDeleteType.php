<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\IsTrue;

final class QuarantinedDocumentationDeleteType extends AbstractType
{
    /**
     * @template T
     *
     * @param FormBuilderInterface<T> $builder
     * @param array<mixed>            $options
     */
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('delete', CheckboxType::class, [
                'label' => 'Do you want to delete this quarantined render request? Any related quarantined items will stay untouched.',
                'label_attr' => [
                    'class' => 'checkbox-custom',
                ],
                'constraints' => [new IsTrue()],
            ])
            ->add('trigger', SubmitType::class, [
                'label' => 'Delete',
            ])
        ;
    }
}
