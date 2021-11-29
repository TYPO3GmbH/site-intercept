<?php

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Functional\Fixtures\AdminInterface;

use App\Entity\HistoryEntry;
use App\Enum\HistoryEntryType;
use App\Enum\SplitterStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SplitCoreControllerTestHistoryData extends Fixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $groupId = 'a048046f-3204-45f6-9572-cb7af54ad7d5';
        $manager->persist(
            (new HistoryEntry())
                ->setStatus(SplitterStatus::QUEUED)
                ->setCreatedAt(new \DateTimeImmutable('2019-03-11T10:33:16.803Z'))
                ->setGroupEntry($groupId)
                ->setType(HistoryEntryType::PATCH)
                ->setData(
                    [
                        'message' => 'Queued a core split job to queue',
                        'job_uuid' => $groupId,
                        'type' => 'patch',
                        'status' => 'queued',
                        'sourceBranch' => 'main',
                        'targetBranch' => 'main',
                        'triggeredBy' => 'api',
                    ]
                )
        );

        $manager->persist(
            (new HistoryEntry())
                ->setStatus(SplitterStatus::WORK)
                ->setCreatedAt(new \DateTimeImmutable('2019-03-11T10:34:22.803Z'))
                ->setGroupEntry($groupId)
                ->setType(HistoryEntryType::PATCH)
                ->setData(
                    [
                        'message' => 'Git command error output: Everything up-to-date',
                        'job_uuid' => $groupId,
                        'type' => 'patch',
                        'status' => 'work',
                        'sourceBranch' => 'main',
                        'targetBranch' => 'main',
                        'timestamp' => '2019-03-11T10:34:22.803Z',
                    ]
                )
        );
        $manager->persist(
            (new HistoryEntry())
                ->setStatus(SplitterStatus::DONE)
                ->setCreatedAt(new \DateTimeImmutable('2019-03-11T10:36:27.256Z'))
                ->setGroupEntry($groupId)
                ->setType(HistoryEntryType::PATCH)
                ->setData(
                    [
                        'message' => 'Finished a git split worker job',
                        'job_uuid' => $groupId,
                        'type' => HistoryEntryType::PATCH,
                        'status' => SplitterStatus::DONE,
                        'sourceBranch' => 'main',
                        'targetBranch' => 'main',
                        'timestamp' => '2019-03-11T10:36:27.256Z',
                    ]
                )
        );

        $manager->flush();
    }

    /**
     * Get the order of this fixture
     *
     * @return int
     */
    public function getOrder()
    {
        return 1;
    }
}
