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
 * Form class represents a form to trigger rendering and deployment of documentation
 * fluid view helper reference.
 */
class BambooDocsFluidVhTriggerFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('DRF', SubmitType::class, ['label' => 'Render Fluid View Helper Reference 9.5'])
            ->add('DRF104', SubmitType::class, ['label' => 'Render Fluid View Helper Reference 10.4'])
            ->add('DRFVM', SubmitType::class, ['label' => 'Render Fluid View Helper Reference master'])
        ;
    }
}
