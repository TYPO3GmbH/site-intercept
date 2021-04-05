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
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Form representing a "transfer a mono repo tag to sub split repo" job
 */
class SplitCoreTagFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add(
                'tag',
                TextType::class,
                [
                    'attr' => [
                        'placeholder' => 'v9.5.2',
                    ],
                ]
            )
            ->add('doTag', SubmitType::class, ['label' => 'Trigger sub repo tagging'])
        ;
    }
}
