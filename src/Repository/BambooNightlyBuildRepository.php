<?php

namespace App\Repository;

use App\Entity\BambooNightlyBuild;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method BambooNightlyBuild|null find($id, $lockMode = null, $lockVersion = null)
 * @method BambooNightlyBuild|null findOneBy(array $criteria, array $orderBy = null)
 * @method BambooNightlyBuild[]    findAll()
 * @method BambooNightlyBuild[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BambooNightlyBuildRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, BambooNightlyBuild::class);
    }

    // /**
    //  * @return BambooNightlyBuild[] Returns an array of BambooNightlyBuild objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('b.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?BambooNightlyBuild
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
