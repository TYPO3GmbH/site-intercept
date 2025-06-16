<?php

declare(strict_types=1);

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
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DocumentationJar|null find($id, $lockMode = null, $lockVersion = null)
 * @method DocumentationJar|null findOneBy(array $criteria, array $orderBy = null)
 * @method DocumentationJar[]    findAll()
 * @method DocumentationJar[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<DocumentationJar>
 */
class DocumentationJarRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
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

    /**
     * Find a rendered documentation by package name and TARGET DIRECTORY (not source branch).
     * Example: lolli/enetcache:draft.
     *
     * Used in 're-render docs' command controller
     *
     * @param string $packageIdentifier
     *
     * @return DocumentationJar|null
     */
    public function findByPackageIdentifier(string $packageIdentifier): ?DocumentationJar
    {
        [$packageName, $version] = explode(':', $packageIdentifier);

        return $this->findOneBy(['targetBranchDirectory' => $version, 'packageName' => $packageName]);
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
