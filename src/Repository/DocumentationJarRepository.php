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
    protected static $orderBy = [
        'extensionKey' => 'ASC',
        'branch' => 'DESC',
    ];

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, DocumentationJar::class);
    }

    /**
     * @return DocumentationJar[]
     */
    public function findCommunityExtensions(): array
    {
        return $this->findBy([
            'typeShort' => ['p'],
        ], static::$orderBy);
    }

    /**
     * @return DocumentationJar[]
     */
    public function findAllExtensions(): array
    {
        return $this->findBy([
            'typeShort' => ['p', 'c'],
        ], static::$orderBy);
    }
}
