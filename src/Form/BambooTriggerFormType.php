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
 * Form class represents a form to trigger core bamboo builds
 * with specific patch sets.
 */
class BambooTriggerFormType extends AbstractType
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
            ->add('TYPO3_8_7', SubmitType::class, ['label' => 'Trigger 8.7'])
            ->add('TYPO3_7_6', SubmitType::class, ['label' => 'Trigger 7.6'])
            ->add('nightlyMaster', SubmitType::class, ['label' => 'Trigger nightly master'])
            ->add('nightly8_7', SubmitType::class, ['label' => 'Trigger nightly 8.7'])
        ;
    }
}
