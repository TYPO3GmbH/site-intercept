<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Form class represents a form to trigger core security bamboo builds
 * with specific patch sets.
 */
class BambooCoreSecurityTriggerFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'change',
                IntegerType::class,
                [
                    'attr' => [
                        'placeholder' => 58920,
                    ],
                ]
            )
            ->add(
                'set',
                IntegerType::class,
                [
                    'attr' => [
                        'placeholder' => 5,
                    ],
                    'label' => 'Patch set',
                ]
            )
            ->add('master', SubmitType::class, ['label' => 'Trigger master'])
            ->add('branch9_5', SubmitType::class, ['label' => 'Trigger 9.5'])
        ;
    }
}
