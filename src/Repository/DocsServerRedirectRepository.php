<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\DocsServerRedirect;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DocsServerRedirect|null find($id, $lockMode = null, $lockVersion = null)
 * @method DocsServerRedirect|null findOneBy(array $criteria, array $orderBy = null)
 * @method DocsServerRedirect[]    findAll()
 * @method DocsServerRedirect[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<DocsServerRedirect>
 */
class DocsServerRedirectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocsServerRedirect::class);
    }
}
