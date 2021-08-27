<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\HistoryEntry;
use App\Enum\HistoryEntryType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use function Doctrine\ORM\QueryBuilder;

/**
 * @method HistoryEntry|null find($id, $lockMode = null, $lockVersion = null)
 * @method HistoryEntry|null findOneBy(array $criteria, array $orderBy = null)
 * @method HistoryEntry[]    findAll()
 * @method HistoryEntry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
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

    public function findSplitLogsByStatus(array $status, int $limit = 100): array
    {
        $qb = $this->createQueryBuilder('h');
        return $qb
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->orX(
                        $qb->expr()->eq('h.type', HistoryEntryType::PATCH),
                        $qb->expr()->eq('h.type', HistoryEntryType::TAG),
                    ),
                    $qb->expr()->in('h.status', $status)
                )
            )
            ->orderBy('h.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
    public function findSplitLogsByStatusAndGroup(array $status, string $group, int $limit = 100): array
    {
        $qb = $this->createQueryBuilder('h');
        return $qb
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->orX(
                        $qb->expr()->eq('h.type', HistoryEntryType::PATCH),
                        $qb->expr()->eq('h.type', HistoryEntryType::TAG),
                    ),
                    $qb->expr()->in('h.status', $status),
                    $qb->expr()->eq('h.group', $group),
                )
            )
            ->orderBy('h.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /*
    public function findOneBySomeField($value): ?HistoryEntry
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
