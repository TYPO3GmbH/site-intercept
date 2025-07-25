<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\HistoryEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method HistoryEntry|null find($id, $lockMode = null, $lockVersion = null)
 * @method HistoryEntry|null findOneBy(array $criteria, array $orderBy = null)
 * @method HistoryEntry[]    findAll()
 * @method HistoryEntry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<HistoryEntry>
 */
class HistoryEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HistoryEntry::class);
    }

    public function findByType(string $type, int $limit = 10): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.type = :val')
            ->setParameter('val', $type)
            ->orderBy('h.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function deleteOldEntries(): void
    {
        $date = (new \DateTimeImmutable('-1 week'));
        $qb = $this->createQueryBuilder('h');
        $qb
            ->delete(HistoryEntry::class, 'h')
            ->where(
                $qb->expr()->lt('h.createdAt', ':date')
            )
            ->setParameter('date', $date->format('Y-m-d H:i:s'))
            ->getQuery()
            ->execute();
    }
}
