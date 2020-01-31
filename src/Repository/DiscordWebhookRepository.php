<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\DiscordWebhook;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DiscordWebhook|null find($id, $lockMode = null, $lockVersion = null)
 * @method DiscordWebhook|null findOneBy(array $criteria, array $orderBy = null)
 * @method DiscordWebhook[]    findAll()
 * @method DiscordWebhook[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DiscordWebhookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DiscordWebhook::class);
    }
}
