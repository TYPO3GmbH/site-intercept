<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\DocumentationQuarantine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DocumentationQuarantine|null find($id, $lockMode = null, $lockVersion = null)
 * @method DocumentationQuarantine|null findOneBy(array $criteria, array $orderBy = null)
 * @method DocumentationQuarantine[]    findAll()
 * @method DocumentationQuarantine[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<DocumentationQuarantine>
 */
class DocumentationQuarantineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocumentationQuarantine::class);
    }
}
