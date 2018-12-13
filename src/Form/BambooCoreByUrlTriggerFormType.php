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
 * Form class represents a form to trigger core bamboo builds
 * by a gerrit review url.
 */
class BambooCoreByUrlTriggerFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'url',
                TextType::class,
                [
                    'attr' => [
                        'placeholder' => 'https://review.typo3.org/#/c/48574/',
                    ],
                ]
            )
            ->add('trigger', SubmitType::class, ['label' => 'Trigger bamboo'])
        ;
    }
}
