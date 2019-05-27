<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Form;

use App\Entity\DocumentationJar;
use App\Service\GitRepositoryService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form class represents a form to manage DocumentationJar entries
 */
class DocumentationDeployment extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $optionsStep1 = $options['step2'] || $options['step3'] ? [
            'attr' => [
                'class' => 'disabled',
                'readonly' => 'readonly'
            ],
        ] : [];
        $optionsStep2 = $options['step3'] ? [
            'attr' => [
                'class' => 'disabled',
                'readonly' => 'readonly'
            ],
        ] : [];

        $builder
            ->add('repositoryUrl', null, $optionsStep1);

        if ($options['step2']) {
            $builder
                ->add('branch', ChoiceType::class, $optionsStep2);
        }

        if ($options['step3']) {
            $builder
                ->add('branch', null, $optionsStep2)
                ->add('publicComposerJsonUrl');
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event) use ($optionsStep2, $options) {
            if ($options['step2']) {
                $repositoryUrl = $event->getData()->getRepositoryUrl();
                $branches = (new GitRepositoryService())->getBranchesFromRepositoryUrl($repositoryUrl);
                $documentationJarRepository = $options['entity_manager']->getRepository(DocumentationJar::class);
                foreach ($branches as $key => $branch) {
                    $jar = $documentationJarRepository
                        ->findBy([
                            'repositoryUrl' => $repositoryUrl,
                            'branch' => $key,
                        ]);

                    if (!empty($jar)) {
                        unset($branches[$key]);
                    }
                }

                $optionsStep2['choices'] = array_flip($branches);

                $event->getForm()->add('branch', ChoiceType::class, $optionsStep2);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DocumentationJar::class,
            'csrf_protection' => getenv('APP_ENV') !== 'test',
            'validation_groups' => false,
            'step1' => true,
            'step2' => false,
            'step3' => false,
            'entity_manager' => null,
        ]);
    }
}
