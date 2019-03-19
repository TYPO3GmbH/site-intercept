<?php

namespace App\Form;

use App\Entity\Redirect;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RedirectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('source')
            ->add('target')
            ->add('statusCode', ChoiceType::class, [
                'choices' => Redirect::$allowedStatusCodes,
                'choice_label' => function($value) {
                    return $value;
                }
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Redirect::class,
        ]);
    }
}
