<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Command;

use App\Repository\DiscordScheduledMessageRepository;
use App\Service\DiscordWebhookService;
use DateTime;
use DateTimeZone;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Woeler\DiscordPhp\Message\DiscordTextMessage;

class SendDiscordScheduledMessagesCommand extends Command
{
    protected static $defaultName = 'app:discord-send';

    /**
     * @var DiscordScheduledMessageRepository
     */
    protected $discordScheduledMessageRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var DiscordWebhookService
     */
    protected $discordWebhookService;

    /**
     * SyncDiscordChannelsCommand constructor.
     * @param DiscordScheduledMessageRepository $discordScheduledMessageRepository
     * @param LoggerInterface $logger
     */
    public function __construct(DiscordScheduledMessageRepository $discordScheduledMessageRepository, LoggerInterface $logger, DiscordWebhookService $discordWebhookService)
    {
        parent::__construct();
        $this->discordScheduledMessageRepository = $discordScheduledMessageRepository;
        $this->logger = $logger;
        $this->discordWebhookService = $discordWebhookService;
    }

    protected function configure()
    {
        $this
            ->setDescription('Send scheduled messages to Discord')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dateTime = new DateTime('now', new DateTimeZone('UTC'));

        $messages = $this->discordScheduledMessageRepository->findWhereChannelIdNotNull();

        foreach ($messages as $message) {
            if ($message->getSchedule()->isDue($dateTime, $message->getTimezone())) {
                $discordMessage = new DiscordTextMessage();
                if (null !== $message->getUsername()) {
                    $discordMessage->setUsername($message->getUsername());
                }

                $discordMessage->setAvatar($message->getAvatarUrl() ?? 'https://intercept.typo3.com/build/images/webhookavatars/default.png');

                $discordMessage->setContent($message->getMessage());

                try {
                    $this->discordWebhookService->sendMessage($discordMessage, $message->getChannel()->getWebhookUrl());
                } catch (GuzzleException $e) {
                    $this->logger->warning(
                        'An error occurred sending a message to channel: #' . $message->getChannel()->getChannelName(),
                        ['scheduledMessage' => $message]
                    );
                }
            }
        }
    }
}
