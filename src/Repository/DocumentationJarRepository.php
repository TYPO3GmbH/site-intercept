<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\DocumentationJar;
use App\Enum\DocumentationStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method DocumentationJar|null find($id, $lockMode = null, $lockVersion = null)
 * @method DocumentationJar|null findOneBy(array $criteria, array $orderBy = null)
 * @method DocumentationJar[]    findAll()
 * @method DocumentationJar[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DocumentationJarRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, DocumentationJar::class);
    }

    /**
     * @return DocumentationJar[]
     */
    public function findAvailableCommunityExtensions(): array
    {
        return $this->findByTypeShort(['p']);
    }

    /**
     * @return DocumentationJar[]
     */
    public function findAllAvailableExtensions(): array
    {
        return $this->findByTypeShort(['c', 'p']);
    }

    private function findByTypeShort(array $typeShort): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.typeShort IN(:type_short)')
            ->andWhere('d.targetBranchDirectory != :target_branch_directory')
            ->andWhere('d.status IN(:status)')
            ->setParameter('type_short', $typeShort)
            ->setParameter('target_branch_directory', 'draft')
            ->setParameter('status', [DocumentationStatus::STATUS_RENDERING, DocumentationStatus::STATUS_RENDERED])
            ->orderBy('d.extensionKey', 'ASC')
            ->addOrderBy('d.branch', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
