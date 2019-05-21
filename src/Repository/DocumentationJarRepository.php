<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\DocumentationJar;
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
    public function findCommunityExtensions(): array
    {
        return $this->findByTypeShort('p');
    }

    /**
     * @return DocumentationJar[]
     */
    public function findCoreExtensions(): array
    {
        return $this->findByTypeShort('c');
    }

    /**
     * @param string $typeShort
     * @return DocumentationJar[]
     */
    private function findByTypeShort(string $typeShort): array
    {
        return $this->createQueryBuilder('d')
            ->where(
                'd.typeShort = :type_short'
            )
            ->setParameter('type_short', $typeShort)
            ->orderBy('d.extensionKey', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
