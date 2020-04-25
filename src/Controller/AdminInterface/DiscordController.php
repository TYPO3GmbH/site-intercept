<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller\AdminInterface;

use App\Entity\DiscordChannel;
use App\Entity\DiscordScheduledMessage;
use App\Entity\DiscordWebhook;
use App\Repository\DiscordChannelRepository;
use App\Repository\DiscordScheduledMessageRepository;
use App\Repository\DiscordWebhookRepository;
use App\Service\DiscordWebhookService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\GuzzleException;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Setono\CronExpressionBundle\Form\DataTransformer\CronExpressionToPartsTransformer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Woeler\DiscordPhp\Message\DiscordEmbedsMessage;

class DiscordController extends AbstractController
{
    /**
     * @Route("/admin/discord/howto", name="admin_discord_webhooks_howto")
     * @IsGranted("ROLE_ADMIN")
     */
    public function howTo(): Response
    {
        $jsonExample = '{' . PHP_EOL . '    "message": "This is an error message",' . PHP_EOL . '    "project_name": "My Cool Project",' . PHP_EOL . '    "log_level": 4' . PHP_EOL . '}';
        return $this->render(
            'discord/howto.html.twig',
            [
                'jsonExample' => $jsonExample
            ]
        );
    }

    /**
     * @Route("/admin/discord", name="admin_discord_webhooks")
     * @IsGranted("ROLE_ADMIN")
     *
     * @param Request $request
     * @param DiscordWebhookRepository $discordWebhookRepository
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function webhookList(
        Request $request,
        DiscordWebhookRepository $discordWebhookRepository,
        PaginatorInterface $paginator
    ): Response {
        $hooks = $discordWebhookRepository->findAll();
        $pagination = $paginator->paginate(
            $hooks,
            $request->query->getInt('page', 1)
        );

        return $this->render(
            'discord/webhook_list.html.twig',
            [
                'pagination' => $pagination,
            ]
        );
    }

    /**
     * @Route("/admin/discord/webhook/delete/{webhookId}", name="admin_discord_webhooks_delete_action")
     * @IsGranted("ROLE_ADMIN")
     *
     * @param int $webhookId
     * @param DiscordWebhookRepository $discordWebhookRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function webhookDelete(
        int $webhookId,
        DiscordWebhookRepository $discordWebhookRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $hook = $discordWebhookRepository->find($webhookId);

        if (null !== $hook) {
            $entityManager->remove($hook);
            $entityManager->flush();
            $this->addFlash('success', 'Discord webhook deleted');
        }

        return $this->redirectToRoute('admin_discord_webhooks');
    }

    /**
     * @Route("/admin/discord/webhook/add", name="admin_discord_webhooks_add_action")
     * @IsGranted("ROLE_ADMIN")
     *
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param DiscordChannelRepository $discordChannelRepository
     * @param DiscordWebhookRepository $discordWebhookRepository
     * @return Response
     * @throws \Exception
     */
    public function webhookAdd(
        Request $request,
        EntityManagerInterface $entityManager,
        DiscordChannelRepository $discordChannelRepository,
        DiscordWebhookRepository $discordWebhookRepository
    ): Response {
        $hook = new DiscordWebhook();
        $form = $this->createForm(\App\Form\DiscordWebhook::class, $hook, ['entity_manager' => $this->getDoctrine()->getManager()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $discordWebHook = $request->get('discord_webhook');
            $channel = $discordChannelRepository->findOneBy(['channelId' => $discordWebHook['channelId']]);
            if (null !== $channel) {
                $this->setHookPropertiesFromRequest($hook, $discordWebHook, $channel);
                $hook->setIdentifier(sha1(random_bytes(25)));

                // Prevent duplicates in the very rare case it might happen
                while ([] !== $discordWebhookRepository->findBy(['identifier' => $hook->getIdentifier()])) {
                    $hook->setIdentifier(sha1(random_bytes(25)));
                }

                $entityManager->persist($hook);
                $entityManager->flush();
                $this->addFlash('success', 'Discord webhook created for channel: #' . $channel->getChannelName()
                    . '<br>' . 'The webhook URL is: ' . $request->getScheme() . '://' . $request->getHttpHost() . '/discord/hook/' . $hook->getIdentifier());

                return $this->redirectToRoute('admin_discord_webhooks');
            }
        }

        return $this->render(
            'discord/webhook_form.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/admin/discord/webhook/edit/{webhookId}", name="admin_discord_webhooks_edit_action")
     * @IsGranted("ROLE_ADMIN")
     *
     * @param Request $request
     * @param int $webhookId
     * @param EntityManagerInterface $entityManager
     * @param DiscordChannelRepository $discordChannelRepository
     * @param DiscordWebhookRepository $discordWebhookRepository
     * @param DiscordWebhookService $discordWebhookService
     * @return Response
     */
    public function webhookEdit(
        Request $request,
        int $webhookId,
        EntityManagerInterface $entityManager,
        DiscordChannelRepository $discordChannelRepository,
        DiscordWebhookRepository $discordWebhookRepository,
        DiscordWebhookService $discordWebhookService
    ): Response {
        $hook = $discordWebhookRepository->find($webhookId);

        if (null === $hook) {
            return $this->redirectToRoute('admin_discord_webhooks_add_action');
        }

        $description = '**OLD**' . PHP_EOL;
        $description .= 'Name: ' . $hook->getName() . PHP_EOL;
        if ($hook->getChannel() !== null) {
            $description .= 'Channel: ' . $hook->getChannel()->getChannelName() . PHP_EOL;
        }

        $form = $this->createForm(\App\Form\DiscordWebhook::class, $hook, ['entity_manager' => $this->getDoctrine()->getManager()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $hookFromRequest = $request->get('discord_webhook');
            $channel = $discordChannelRepository->findOneBy(['channelId' => $hookFromRequest['channelId']]);
            if (null !== $channel) {
                $this->setHookPropertiesFromRequest($hook, $hookFromRequest, $channel);

                $entityManager->persist($hook);
                $entityManager->flush();
                $this->addFlash('success', 'Discord webhook updated for channel: #' . $channel->getChannelName());

                $description .= PHP_EOL . '**NEW**' . PHP_EOL;
                $description .= 'Name: ' . $hook->getName() . PHP_EOL;
                $description .= 'Channel: ' . $hook->getChannel()->getChannelName() . PHP_EOL;

                $message = new DiscordEmbedsMessage();
                $message->setTitle('Webhook configuration was updated')
                    ->setColorWithHexValue('ff8700')
                    ->setFooterText('TYPO3 Intercept')
                    ->setDescription($description)
                    ->addField('Webhook Name', $hook->getName(), true)
                    ->addField('Edited By', $this->getUser()->getUsername(), true);
                $message->setUsername($hook->getUsername() ?? 'Intercept');
                $message->setAvatar($hook->getAvatarUrl() ?? '');

                try {
                    $discordWebhookService->sendMessage($message, $hook->getChannel()->getWebhookUrl());
                } catch (GuzzleException $e) {
                }

                return $this->redirectToRoute('admin_discord_webhooks');
            }
        }

        if ($hook->getChannel() !== null) {
            $form->get('channelId')->setData($hook->getChannel()->getChannelId());
        } else {
            $form->get('channelId')->setData(null);
        }

        return $this->render(
            'discord/webhook_form.html.twig',
            [
                'form' => $form->createView(),
                'edit' => true,
            ]
        );
    }

    /**
     * @Route("/admin/discord/webhook/test/{webhookId}", name="admin_discord_webhooks_test_action")
     * @IsGranted("ROLE_ADMIN")
     *
     * @param int $webhookId
     * @param DiscordWebhookRepository $discordWebhookRepository
     * @param DiscordWebhookService $discordWebhookService
     * @return Response
     */
    public function webhookTest(
        int $webhookId,
        DiscordWebhookRepository $discordWebhookRepository,
        DiscordWebhookService $discordWebhookService
    ): Response {
        $hook = $discordWebhookRepository->find($webhookId);

        if (null !== $hook && null !== $hook->getChannel()) {
            $message = new DiscordEmbedsMessage();
            $message->setTitle('Intercept Test Message')
                ->setColorWithHexValue('ff8700')
                ->setFooterText('TYPO3 Intercept')
                ->addField('Webhook Name', $hook->getName(), true)
                ->addField('Triggered By', $this->getUser()->getUsername(), true);

            if (null !== $hook->getUsername()) {
                $message->setUsername($hook->getUsername());
            }
            if (null !== $hook->getAvatarUrl()) {
                $message->setAvatar($hook->getAvatarUrl());
            }

            try {
                $discordWebhookService->sendMessage($message, $hook->getChannel()->getWebhookUrl());
                $this->addFlash('success', 'A test message was sent to Discord in channel: #' . $hook->getChannel()->getChannelName());
            } catch (GuzzleException $e) {
                $this->addFlash('danger', 'An error occurred sending a message to channel: #' . $hook->getChannel()->getChannelName());
            }
        }

        return $this->redirectToRoute('admin_discord_webhooks');
    }

    /**
     * @Route("/admin/discord/scheduled", name="admin_discord_scheduled_messages")
     * @IsGranted("ROLE_ADMIN")
     *
     * @param Request $request
     * @param DiscordScheduledMessageRepository $discordScheduledMessageRepository
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function scheduledMessageList(
        Request $request,
        DiscordScheduledMessageRepository $discordScheduledMessageRepository,
        PaginatorInterface $paginator
    ): Response {
        $messaged = $discordScheduledMessageRepository->findAll();
        $pagination = $paginator->paginate(
            $messaged,
            $request->query->getInt('page', 1)
        );

        return $this->render(
            'discord/scheduled_message_list.html.twig',
            [
                'pagination' => $pagination,
            ]
        );
    }

    /**
     * @Route("/admin/discord/scheduled/add", name="admin_discord_scheduled_messages_add_action")
     * @IsGranted("ROLE_ADMIN")
     *
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param DiscordChannelRepository $discordChannelRepository
     * @return Response
     * @throws \Exception
     */
    public function scheduledMessageAdd(
        Request $request,
        EntityManagerInterface $entityManager,
        DiscordChannelRepository $discordChannelRepository
    ): Response {
        $response = null;
        $message = new DiscordScheduledMessage();
        $form = $this->createForm(\App\Form\DiscordScheduledMessage::class, $message, ['entity_manager' => $this->getDoctrine()->getManager()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $response = $this->handleFormSubmit($request->get('discord_scheduled_message'), $entityManager, $discordChannelRepository, $message);
        }

        return $response ?? $this->render(
            'discord/scheduled_message_form.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    /**
     * @Route("/admin/discord/scheduled/edit/{messageId}", name="admin_discord_scheduled_messages_edit_action")
     * @IsGranted("ROLE_ADMIN")
     *
     * @param int $messageId
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param DiscordChannelRepository $discordChannelRepository
     * @param DiscordScheduledMessageRepository $discordScheduledMessageRepository
     * @return Response
     */
    public function scheduledMessageEdit(
        int $messageId,
        Request $request,
        EntityManagerInterface $entityManager,
        DiscordChannelRepository $discordChannelRepository,
        DiscordScheduledMessageRepository $discordScheduledMessageRepository
    ): Response {
        $message = $discordScheduledMessageRepository->find($messageId);

        if (null === $message) {
            return $this->redirectToRoute('admin_discord_scheduled_messages_add_action');
        }

        $form = $this->createForm(\App\Form\DiscordScheduledMessage::class, $message, ['entity_manager' => $this->getDoctrine()->getManager()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $response = $this->handleFormSubmit($request->get('discord_scheduled_message'), $entityManager, $discordChannelRepository, $message);
            if ($response !== null) {
                return $response;
            }
        }

        if ($message->getChannel() !== null) {
            $form->get('channelId')->setData($message->getChannel()->getChannelId());
        } else {
            $form->get('channelId')->setData(null);
        }

        return $this->render(
            'discord/scheduled_message_form.html.twig',
            [
                'form' => $form->createView(),
                'edit' => true,
            ]
        );
    }

    /**
     * @Route("/admin/discord/scheduled/delete/{messageId}", name="admin_discord_scheduled_messages_delete_action")
     * @IsGranted("ROLE_ADMIN")
     *
     * @param int $messageId
     * @param DiscordScheduledMessageRepository $discordScheduledMessageRepository
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function scheduledMessageDelete(
        int $messageId,
        DiscordScheduledMessageRepository $discordScheduledMessageRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $message = $discordScheduledMessageRepository->find($messageId);

        if (null !== $message) {
            $entityManager->remove($message);
            $entityManager->flush();
            $this->addFlash('success', 'Discord message deleted');
        }

        return $this->redirectToRoute('admin_discord_scheduled_messages');
    }

    /**
     * @param array $discordScheduledMessage
     * @param EntityManagerInterface $entityManager
     * @param DiscordChannelRepository $discordChannelRepository
     * @param DiscordScheduledMessage|null $message
     * @return RedirectResponse|null
     */
    protected function handleFormSubmit(
        array $discordScheduledMessage,
        EntityManagerInterface $entityManager,
        DiscordChannelRepository $discordChannelRepository,
        DiscordScheduledMessage $message
    ): ?Response {
        $channel = $discordChannelRepository->findOneBy(['channelId' => $discordScheduledMessage['channelId']]);
        if (null !== $channel) {
            $transformer = new CronExpressionToPartsTransformer();
            $cronArray = ['minutes' => [], 'hours' => [], 'days' => [], 'weekdays' => [], 'months' => []];
            $message->setName($discordScheduledMessage['name'])
                ->setChannel($channel)
                ->setMessage($discordScheduledMessage['message'])
                ->setSchedule($transformer->reverseTransform(array_merge($cronArray, $discordScheduledMessage['schedule'])))
                ->setTimezone($discordScheduledMessage['timezone']);

            if (!empty($discordScheduledMessage['username'])) {
                $message->setUsername($discordScheduledMessage['username']);
            }
            if (!empty($discordScheduledMessage['avatarUrl'])) {
                $message->setAvatarUrl($discordScheduledMessage['avatarUrl']);
            }

            $entityManager->persist($message);
            $entityManager->flush();
            $this->addFlash('success', 'Discord message created for channel: #' . $channel->getChannelName());

            return $this->redirectToRoute('admin_discord_scheduled_messages');
        }
        return null;
    }

    protected function setHookPropertiesFromRequest(
        DiscordWebhook $hook,
        $discordWebHook,
        ?DiscordChannel $channel
    ): void {
        $hook->setName($discordWebHook['name'])
            ->setChannel($channel)
            ->setType($discordWebHook['type']);

        if (!empty($discordWebHook['username'])) {
            $hook->setUsername($discordWebHook['username']);
        }
        if (!empty($discordWebHook['avatarUrl'])) {
            $hook->setAvatarUrl($discordWebHook['avatarUrl']);
        }
        if (!empty($discordWebHook['loglevel'])) {
            $hook->setLogLevel($discordWebHook['loglevel']);
        }
    }
}
