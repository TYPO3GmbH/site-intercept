<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Entity\DocumentationQuarantine;
use App\Extractor\PushEvent;
use App\Repository\DocumentationQuarantineRepository;
use Doctrine\ORM\EntityManagerInterface;

class DocumentationQuarantineService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DocumentationQuarantineRepository $documentationQuarantineRepository,
    ) {
    }

    public function isQuarantined(PushEvent $pushEvent): bool
    {
        return null !== $this->documentationQuarantineRepository->findOneBy([
            'checksum' => $this->hash($pushEvent),
        ]);
    }

    /**
     * The domain has to be the one the request was actually blocked on, which is
     * the host of the composer.json url. It differs from the host of the clone url
     * for Github always, and can differ for the other services as well.
     */
    public function quarantine(PushEvent $pushEvent, string $blockedDomain): DocumentationQuarantine
    {
        $documentationQuarantine = (new DocumentationQuarantine())
            ->setDomain($blockedDomain)
            ->setSerializedPushEvent(json_encode($pushEvent, JSON_THROW_ON_ERROR))
            ->setChecksum($this->hash($pushEvent));

        $this->entityManager->persist($documentationQuarantine);
        $this->entityManager->flush();

        return $documentationQuarantine;
    }

    /**
     * @return DocumentationQuarantine[]
     */
    public function findAllByDomain(string $domain): array
    {
        return $this->documentationQuarantineRepository->findBy(['domain' => $domain]);
    }

    private function hash(PushEvent $pushEvent): string
    {
        return hash('sha256', json_encode($pushEvent, JSON_THROW_ON_ERROR));
    }
}
