<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\DiscordChannel;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method DiscordChannel|null find($id, $lockMode = null, $lockVersion = null)
 * @method DiscordChannel|null findOneBy(array $criteria, array $orderBy = null)
 * @method DiscordChannel[]    findAll()
 * @method DiscordChannel[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DiscordChannelRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, DiscordChannel::class);
    }

    /**
     * @return DiscordChannel[]
     */
    public function findTextChannels(): array
    {
        return $this->findBy(['channelType' => DiscordChannel::CHANNEL_TYPE_TEXT]);
    }

    /**
     * @return DiscordChannel[]
     */
    public function findVoiceChannels(): array
    {
        return $this->findBy(['channelType' => DiscordChannel::CHANNEL_TYPE_VOICE]);
    }

    /**
     * @return DiscordChannel[]
     */
    public function findCategories(): array
    {
        return $this->findBy(['channelType' => DiscordChannel::CHANNEL_TYPE_CATEGORY]);
    }

    /**
     * @return DiscordChannel[]
     */
    public function findWhereChannelIdNotIn(array $channelIds): array
    {
        $qb = $this->createQueryBuilder('e');

        return $qb->select('e')
            ->where($qb->expr()->notIn('e.channelId', $channelIds))
            ->getQuery()
            ->getResult();
    }
}
