<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller;

use App\Repository\DiscordChannelRepository;
use App\Repository\DiscordWebhookRepository;
use App\Service\DiscordWebhookService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Exception\GuzzleException;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Woeler\DiscordPhp\Message\DiscordEmbedsMessage;

class AdminInterfaceDiscordController extends AbstractController
{
    /**
     * @Route("/admin/discord", name="admin_discord_webhooks")
     * @IsGranted("ROLE_ADMIN")
     *
     * @param Request $request
     * @param DiscordWebhookRepository $discordWebhookRepository
     * @param PaginatorInterface $paginator
     * @return Response
     */
    public function index(Request $request, DiscordWebhookRepository $discordWebhookRepository, PaginatorInterface $paginator): Response
    {
        $hooks = $discordWebhookRepository->findAll();
        $pagination = $paginator->paginate(
            $hooks,
            $request->query->getInt('page', 1)
        );

        return $this->render(
            'discordHooks.html.twig',
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
    public function delete(int $webhookId, DiscordWebhookRepository $discordWebhookRepository, EntityManagerInterface $entityManager): Response
    {
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
    public function add(
        Request $request,
        EntityManagerInterface $entityManager,
        DiscordChannelRepository $discordChannelRepository,
        DiscordWebhookRepository $discordWebhookRepository
    ): Response {
        $hook = new \App\Entity\DiscordWebhook();
        $form = $this->createForm(\App\Form\DiscordWebhook::class, $hook, ['entity_manager' => $this->getDoctrine()->getManager()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $channel = $discordChannelRepository->findOneBy(['channelId' => $request->get('discord_webhook')['channelId']]);
            if (null !== $channel) {
                $hook->setName($request->get('discord_webhook')['name'])
                    ->setChannel($channel)
                    ->setType($request->get('discord_webhook')['type']);

                if (!empty($request->get('discord_webhook')['username'])) {
                    $hook->setUsername($request->get('discord_webhook')['username']);
                }
                if (!empty($request->get('discord_webhook')['avatarUrl'])) {
                    $hook->setAvatarUrl($request->get('discord_webhook')['avatarUrl']);
                }
                if (!empty($request->get('discord_webhook')['loglevel'])) {
                    $hook->setLogLevel($request->get('discord_webhook')['loglevel']);
                }
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
            'discord_webhooks/addWebhook.html.twig',
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
    public function edit(
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
            $channel = $discordChannelRepository->findOneBy(['channelId' => $request->get('discord_webhook')['channelId']]);
            if (null !== $channel) {
                $hook->setName($request->get('discord_webhook')['name'])
                    ->setChannel($channel)
                    ->setType($request->get('discord_webhook')['type']);

                if (!empty($request->get('discord_webhook')['username'])) {
                    $hook->setUsername($request->get('discord_webhook')['username']);
                }
                if (!empty($request->get('discord_webhook')['avatarUrl'])) {
                    $hook->setAvatarUrl($request->get('discord_webhook')['avatarUrl']);
                }
                if (!empty($request->get('discord_webhook')['loglevel'])) {
                    $hook->setLogLevel($request->get('discord_webhook')['loglevel']);
                }

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
            'discord_webhooks/addWebhook.html.twig',
            [
                'form' => $form->createView(),
                'edit' => true,
            ]
        );
    }
    /**
     * @Route("/admin/discord/howto", name="admin_discord_webhooks_howto")
     * @IsGranted("ROLE_ADMIN")
     */
    public function howTo(): Response
    {
        $jsonExample = '{' . PHP_EOL . '    "message": "This is an error message",' . PHP_EOL . '    "project_name": "My Cool Project",' . PHP_EOL . '    "log_level": 4' . PHP_EOL . '}';
        return $this->render('discord_webhooks/howto.html.twig', ['jsonExample' => $jsonExample]);
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
    public function testWebhook(int $webhookId, DiscordWebhookRepository $discordWebhookRepository, DiscordWebhookService $discordWebhookService): Response
    {
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
}
