<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Form;

use App\Entity\DiscordChannel;
use App\Repository\DiscordChannelRepository;
use App\Utility\TimeUtility;
use Setono\CronExpressionBundle\Form\Type\CronExpressionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DiscordScheduledMessage extends AbstractType
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
            ->add('message', TextareaType::class, ['required' => true])
            ->add(
                'schedule',
                CronExpressionType::class,
                [
                    'required' => true,
                    'label' => 'Cron interval'
                ]
            )
            ->add(
                'timezone',
                ChoiceType::class,
                [
                    'choices' => array_flip(TimeUtility::timeZones()),
                    'required' => true,
                    'data' => 'Europe/Berlin'
                ]
            )
            ->add('username', null)
            ->add('avatarUrl', null)
            ->add('submit', SubmitType::class, [
                'label' => 'Save',
                'attr' => ['class' => 'btn btn-primary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => \App\Entity\DiscordScheduledMessage::class,
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

        return array_flip($channels);
    }
}
