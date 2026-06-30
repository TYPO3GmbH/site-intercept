<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Form;

use App\Entity\KnownRepositoryDomain;
use App\Enum\RepositoryDomainStatus;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

final class KnownDomainCreateType extends AbstractType
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

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
            ->add(
                'domain',
                TextType::class,
                [
                    'required' => true,
                    'label' => 'Domain name',
                    'attr' => ['maxlength' => 255],
                ]
            )
            ->add(
                'status',
                ChoiceType::class,
                [
                    'required' => true,
                    'label' => 'Status',
                    'choices' => [
                        RepositoryDomainStatus::ALLOWED->getLabel() => RepositoryDomainStatus::ALLOWED,
                        RepositoryDomainStatus::DISALLOWED->getLabel() => RepositoryDomainStatus::DISALLOWED,
                    ],
                    'constraints' => [new NotBlank()],
                ]
            );

        if ($this->security->isGranted('ROLE_ADMIN')) {
            $builder->add('locked', CheckboxType::class, [
                'label' => 'Lock domain',
                'required' => false,
            ]);
        }

        $builder->add('trigger', SubmitType::class, [
            'label' => 'Save',
            'attr' => ['class' => 'btn btn-primary'],
        ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => KnownRepositoryDomain::class,
        ]);
    }
}
