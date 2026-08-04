<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Unit\Service;

use App\Entity\DocumentationQuarantine;
use App\Extractor\PushEvent;
use App\Repository\DocumentationQuarantineRepository;
use App\Service\DocumentationQuarantineService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class DocumentationQuarantineServiceTest extends TestCase
{
    /**
     * Approving a quarantined entry allowlists the domain it recorded, and that
     * decision is only correct if the recorded domain is the one the request was
     * blocked on. For Github those two never match: the clone url is on
     * github.com while the composer.json is fetched from
     * raw.githubusercontent.com.
     */
    public function testQuarantineRecordsTheDomainTheRequestWasBlockedOn(): void
    {
        $persisted = null;
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('persist')->willReturnCallback(
            static function (object $entity) use (&$persisted): void {
                $persisted = $entity;
            }
        );

        $subject = new DocumentationQuarantineService($entityManager, $this->createMock(DocumentationQuarantineRepository::class));
        $pushEvent = new PushEvent(
            'https://github.com/acme/coolextension.git',
            'main',
            'https://raw.githubusercontent.com/acme/coolextension/main/composer.json',
            '{}'
        );

        $subject->quarantine($pushEvent, 'raw.githubusercontent.com');

        $this->assertInstanceOf(DocumentationQuarantine::class, $persisted);
        $this->assertSame('raw.githubusercontent.com', $persisted->getDomain());
    }
}
