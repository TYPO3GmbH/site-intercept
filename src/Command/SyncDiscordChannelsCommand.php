<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Command;

use App\Entity\DiscordChannel;
use App\Exception\UnexpectedDiscordApiResponseException;
use App\Repository\DiscordChannelRepository;
use App\Repository\DiscordScheduledMessageRepository;
use App\Repository\DiscordWebhookRepository;
use App\Service\DiscordServerService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncDiscordChannelsCommand extends Command
{
    protected static $defaultName = 'app:discord-sync';

    /**
     * @var DiscordServerService
     */
    protected $discordServerService;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var DiscordChannelRepository
     */
    protected $discordChannelRepository;

    /**
     * @var DiscordWebhookRepository
     */
    protected $discordWebhookRepository;

    /**
     * @var DiscordScheduledMessageRepository
     */
    protected $discordScheduledMessageRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * SyncDiscordChannelsCommand constructor.
     * @param DiscordServerService $discordServerService
     * @param EntityManagerInterface $entityManager
     * @param DiscordChannelRepository $discordChannelRepository
     * @param LoggerInterface $logger
     * @param DiscordWebhookRepository $discordWebhookRepository
     * @param DiscordScheduledMessageRepository $discordScheduledMessageRepository
     */
    public function __construct(
        DiscordServerService $discordServerService,
        EntityManagerInterface $entityManager,
        DiscordChannelRepository $discordChannelRepository,
        LoggerInterface $logger,
        DiscordWebhookRepository $discordWebhookRepository,
        DiscordScheduledMessageRepository $discordScheduledMessageRepository
    ) {
        parent::__construct();
        $this->discordServerService = $discordServerService;
        $this->entityManager = $entityManager;
        $this->discordChannelRepository = $discordChannelRepository;
        $this->discordWebhookRepository = $discordWebhookRepository;
        $this->discordScheduledMessageRepository = $discordScheduledMessageRepository;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this
            ->setDescription('Sync the database with the Discord server')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $channels = $this->discordServerService->getChannels();
        } catch (UnexpectedDiscordApiResponseException $e) {
            $this->logger->error(
                'Could not fetch Discord channel list',
                [
                    'message' => $e->getMessage(),
                ]
            );
            return;
        }

        $channelIds = [];

        // Sort the parent categories at the top
        usort($channels, static function ($a, $b) {
            if ($a['parent_id'] === $b['parent_id']) {
                return 0;
            }

            if ($a['parent_id'] === null) {
                return -1;
            }

            return 1;
        });

        foreach ($channels as $channel) {
            if ($channel['parent_id'] !== null) {
                // Save all channels that could be a parent
                $this->entityManager->flush();
            }

            $discordChannel = $this->discordChannelRepository->findOneBy(['channelId' => $channel['id']]);

            if (null === $discordChannel) {
                $discordChannel = (new DiscordChannel())
                    ->setChannelId($channel['id']);
            }

            $discordChannel->setChannelName($channel['name'])
                ->setChannelType($channel['type'])
                ->setParent($this->discordChannelRepository->findOneBy(['channelId' => $channel['parent_id']]));

            if ($channel['type'] === DiscordChannel::CHANNEL_TYPE_TEXT) {
                try {
                    $webhook = $this->discordServerService->createOrGetInterceptHook($channel['id']);
                    $discordChannel->setWebhookUrl('https://discordapp.com/api/webhooks/' . $webhook['id'] . '/' . $webhook['token']);
                } catch (UnexpectedDiscordApiResponseException $e) {
                    $this->logger->error('Could not fetch webhook information for Discord channel ' . $channel['name'], $channel);
                }
            }

            $channelIds[] = $channel['id'];
            $this->entityManager->persist($discordChannel);
        }

        $deletedChannels = $this->discordChannelRepository->findWhereChannelIdNotIn($channelIds);

        foreach ($deletedChannels as $deletedChannel) {
            $hooks = $this->discordWebhookRepository->findBy(['channel' => $deletedChannel]);
            $messages = $this->discordScheduledMessageRepository->findBy(['channel' => $deletedChannel]);

            foreach ($hooks as $hook) {
                $hook->setChannel(null);
                $this->entityManager->persist($hook);
            }
            foreach ($messages as $message) {
                $message->setChannel(null);
                $this->entityManager->persist($message);
            }

            $this->entityManager->remove($deletedChannel);
        }

        $this->entityManager->flush();
    }
}
