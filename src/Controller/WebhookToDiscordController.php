<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller;

use App\Discord\DiscordTransformerFactory;
use App\Exception\DiscordTransformerTypeNotFoundException;
use App\Repository\DiscordWebhookRepository;
use App\Service\DiscordWebhookService;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WebhookToDiscordController extends AbstractController
{
    /**
     * @Route("/discord/hook/{identifier}", name="webhook_to_discord")
     * @param Request $request
     * @param string $identifier
     * @param DiscordWebhookRepository $discordWebhookRepository
     * @param DiscordWebhookService $discordWebhookService
     * @param LoggerInterface $logger
     * @return Response
     */
    public function execute(Request $request, string $identifier, DiscordWebhookRepository $discordWebhookRepository, DiscordWebhookService $discordWebhookService, LoggerInterface $logger): Response
    {
        $hook = $discordWebhookRepository->findOneBy(['identifier' => $identifier]);

        if (null === $hook) {
            return Response::create('Webhook not found', Response::HTTP_NOT_FOUND);
        }

        $channel = $hook->getChannel();

        if (null === $channel) {
            return Response::create('Webhook not mapped to a Discord channel', Response::HTTP_PRECONDITION_FAILED);
        }

        try {
            $transformer = DiscordTransformerFactory::getTransformer($hook->getType());
        } catch (DiscordTransformerTypeNotFoundException $e) {
            return Response::create('Webhook is of an unknown service type', Response::HTTP_PRECONDITION_FAILED);
        }

        $content = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $logger->warning(
                'Could not decode webhook payload',
                ['hook' => $hook, 'payload' => $request->getContent(), 'error' => json_last_error_msg()]
            );
            return Response::create('Could not decode json payload', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (!$transformer->shouldBeSent($content, $hook)) {
            return Response::create('The request was ok, but not all conditions for Discord delivery were met.', Response::HTTP_NO_CONTENT);
        }

        $message = $transformer->getDiscordMessage($content);
        if (null !== $hook->getUsername()) {
            $message->setUsername($hook->getUsername());
        }
        if (null !== $hook->getAvatarUrl()) {
            $message->setAvatar($hook->getAvatarUrl());
        }

        try {
            $discordWebhookService->sendMessage($message, $channel->getWebhookUrl());
        } catch (GuzzleException $e) {
            return Response::create('Discord API returned an error', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return Response::create('Ok', Response::HTTP_OK);
    }
}
