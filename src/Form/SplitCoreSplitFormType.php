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
 * Form class represents a form to trigger core sub tree splitting.
 */
class SplitCoreSplitFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('master', SubmitType::class, ['label' => 'Trigger master'])
            ->add('branch9_5', SubmitType::class, ['label' => 'Trigger 9.5'])
        ;
    }
}
