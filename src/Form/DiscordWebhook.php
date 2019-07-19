<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Form;

use App\Discord\DiscordTransformerFactory;
use App\Entity\DiscordChannel;
use App\Repository\DiscordChannelRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form class represents a form to manage DiscordWebhook entries
 */
class DiscordWebhook extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $discordChannelRepository = $options['entity_manager']->getRepository(DiscordChannel::class);
        $builder
            ->add('name', null, ['required' => true])
            ->add(
                'channelId',
                ChoiceType::class,
                [
                    'choices' => $this->buildDiscordChannelArray($discordChannelRepository),
                    'required' => true,
                    'mapped' => false,
                    'label' => 'Channel'
                ]
            )
            ->add(
                'type',
                ChoiceType::class,
                [
                    'choices' => array_flip(DiscordTransformerFactory::TYPES),
                    'required' => true,
                ]
            )
            ->add(
                'loglevel',
                ChoiceType::class,
                [
                    'choices' => array_flip([
                        0 => 'Emergency >',
                        1 => 'Alert >',
                        2 => 'Critical >',
                        3 => 'Error >',
                        4 => 'Warning >',
                        5 => 'Notice >',
                        6 => 'Info >',
                        7 => 'Debug >',
                    ]),
                    'mapped' => false,
                    'label_attr' => ['id' => 'discord_webhook_loglevel_label']
                ]
            )
            ->add('username', null)
            ->add('avatarUrl', null)
            ->add('submit', SubmitType::class, [
                'label' => 'Save',
                'attr' => ['class' => 'btn btn-primary pull-right'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\DiscordWebhook::class,
            'csrf_protection' => getenv('APP_ENV') !== 'test',
            'validation_groups' => false,
            'entity_manager' => null,
        ]);
    }

    private function buildDiscordChannelArray(DiscordChannelRepository $discordChannelRepository): array
    {
        $channels = [];

        foreach ($discordChannelRepository->findBy(['channelType' => DiscordChannel::CHANNEL_TYPE_TEXT]) as $channel) {
            if (null !== $channel->getParent()) {
                $channels[$channel->getChannelId()] = '#' . $channel->getChannelName() . ' (' . $channel->getParent()->getChannelName() . ')';
            } else {
                $channels[$channel->getChannelId()] = '#' . $channel->getChannelName();
            }
        }

        asort($channels);
        $channels = array_flip($channels);

        return $channels;
    }
}
