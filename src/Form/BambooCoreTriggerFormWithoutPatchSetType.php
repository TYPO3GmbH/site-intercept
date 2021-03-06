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
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Form class represents a form to trigger core bamboo builds
 * without specific patch sets.
 */
class BambooCoreTriggerFormWithoutPatchSetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('master', SubmitType::class, ['label' => 'Trigger master'])
            ->add('branch10_4', SubmitType::class, ['label' => 'Trigger 10.4'])
            ->add('branch9_5', SubmitType::class, ['label' => 'Trigger 9.5'])
            ->add('nightlyMaster', SubmitType::class, ['label' => 'Trigger nightly master'])
            ->add('nightly10_4', SubmitType::class, ['label' => 'Trigger nightly 10.4'])
            ->add('nightly9_5', SubmitType::class, ['label' => 'Trigger nightly 9.5'])
        ;
    }
}
