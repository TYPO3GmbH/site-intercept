<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\DiscordScheduledMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DiscordScheduledMessage|null find($id, $lockMode = null, $lockVersion = null)
 * @method DiscordScheduledMessage|null findOneBy(array $criteria, array $orderBy = null)
 * @method DiscordScheduledMessage[]    findAll()
 * @method DiscordScheduledMessage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DiscordScheduledMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DiscordScheduledMessage::class);
    }

    /**
     * @return DiscordScheduledMessage[]
     */
    public function findWhereChannelIdNotNull(): array
    {
        $qb = $this->createQueryBuilder('e');

        return $qb->select('e')
            ->where($qb->expr()->isNotNull('e.channel'))
            ->getQuery()
            ->getResult();
    }
}
