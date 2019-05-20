<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Form;

use App\Entity\DocsServerRedirect;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class DocsServerRedirectType
 * Form definition for the redirect entity
 */
class DocsServerRedirectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('source', null, [
                'help' => 'The path must start with /p/, /c/, /m/ or /h/ and contains at least the three parts: vendor/package/version/, e.g. \'/p/t3g/blog/9.0/Administration/Index.html\'. Alternatively, add a so-called legacy location which requires the extension name and a version, e.g. \'/typo3cms/extensions/my_exension/1.0/\'',
            ])
            ->add('target', null, [
                'help' => 'The path must start with /p/, /c/, /m/ or /h/ and contains at least the three parts: vendor/package/version/, e.g. \'/p/t3g/blog/9.0/Administration/Index.html\'. For legacy locations, provide a directory only, since every requested file will get redirected.',
            ])
            ->add('statusCode', ChoiceType::class, [
                'choices' => DocsServerRedirect::$allowedStatusCodes,
                'choice_label' => static function ($value) {
                    return $value;
                }
            ])
            ->add('isLegacy', HiddenType::class, [
                'data' => '0'
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SUBMIT, static function (FormEvent $event) {
            $data = $event->getData();
            $data['isLegacy'] = (int)(strpos($data['source'], '/typo3cms/extensions/') === 0);
            $event->setData($data);
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => DocsServerRedirect::class,
            'csrf_protection' => getenv('APP_ENV') !== 'test',
        ]);
    }
}
