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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class DocsDeploymentFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->setMethod('GET')
            ->add('search', SearchType::class, [
                'label' => false,
                ])
            ->add('type', ChoiceType::class, [
                'label' => false,
                'placeholder' => 'All',
                'choices'  => [
                    'core-extension' => 'core-extension',
                    'extension' => 'extension',
                    'manual' => 'manual',
                    'docs-home' => 'docs-home',
                ]
            ])
            ->add('trigger', SubmitType::class, [
                'label' => 'Search'
            ]);
    }

    public function setDefaultOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'csrf_protection' => true,
                'csrf_field_name' => '_token',
            ])
        ;
    }

    public function getName(): string
    {
        return 'docsDeploymentFilter';
    }
}
