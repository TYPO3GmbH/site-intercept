<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\BambooNightlyBuild;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method BambooNightlyBuild|null find($id, $lockMode = null, $lockVersion = null)
 * @method BambooNightlyBuild|null findOneBy(array $criteria, array $orderBy = null)
 * @method BambooNightlyBuild[]    findAll()
 * @method BambooNightlyBuild[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BambooNightlyBuildRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BambooNightlyBuild::class);
    }
}
