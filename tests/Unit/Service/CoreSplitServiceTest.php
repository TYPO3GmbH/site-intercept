<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Unit\Service;

use App\GitWrapper\Event\GitOutputListener;
use App\Service\CoreSplitService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;

class CoreSplitServiceTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function getMonoRepositoryReturnsRepository(): void
    {
        $coreSplitter = new CoreSplitService(
            $this->prophesize(LoggerInterface::class)->reveal(),
            '',
            'git@github.com:typo3/typo3.git',
            'git@github.com:TYPO3.CMS/',
            '',
            $this->prophesize(GitOutputListener::class)->reveal(),
            $this->prophesize(EntityManagerInterface::class)->reveal()
        );

        self::assertSame('typo3/typo3', $coreSplitter->getMonoRepository());
    }

    /**
     * @test
     */
    public function getMonoRepositoryThrowsExceptionOnInvalidData(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot extract repository from clone URL typo3/typo3');
        $this->expectExceptionCode(1632320303);

        $coreSplitter = new CoreSplitService(
            $this->prophesize(LoggerInterface::class)->reveal(),
            '',
            'typo3/typo3',
            'git@github.com:TYPO3.CMS/',
            '',
            $this->prophesize(GitOutputListener::class)->reveal(),
            $this->prophesize(EntityManagerInterface::class)->reveal()
        );

        $coreSplitter->getMonoRepository();
    }
}
