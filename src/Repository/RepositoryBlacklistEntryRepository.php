<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\RepositoryBlacklistEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RepositoryBlacklistEntry|null find($id, $lockMode = null, $lockVersion = null)
 * @method RepositoryBlacklistEntry|null findOneBy(array $criteria, array $orderBy = null)
 * @method RepositoryBlacklistEntry[]    findAll()
 * @method RepositoryBlacklistEntry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RepositoryBlacklistEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RepositoryBlacklistEntry::class);
    }

    public function isBlacklisted(string $repositoryUrl): bool
    {
        return null !== $this->findOneBy(['repositoryUrl' => $repositoryUrl]);
    }
}
