<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\DocsServerRedirect;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method DocsServerRedirect|null find($id, $lockMode = null, $lockVersion = null)
 * @method DocsServerRedirect|null findOneBy(array $criteria, array $orderBy = null)
 * @method DocsServerRedirect[]    findAll()
 * @method DocsServerRedirect[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DocsServerRedirectRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, DocsServerRedirect::class);
    }
}
