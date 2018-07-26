<?php
declare(strict_types = 1);
namespace T3G\Intercept\Tests\Unit\Github;

use PHPUnit\Framework\TestCase;
use T3G\Intercept\Exception\DoNotCareException;
use T3G\Intercept\Github\PushEvent;

class PushEventTest extends TestCase
{
    public function getSourceBranchFromRefReturnsCorrectValueDataProvider()
    {
        return [
            'master' => [
                [
                    'ref' => 'refs/heads/master',
                ],
                'master'
            ],
            'TYPO3_8-7' => [
                [
                    'ref' => 'refs/heads/TYPO3_8-7',
                ],
                'TYPO3_8-7'
            ],
            '9.1' => [
                [
                    'ref' => 'refs/heads/9.1',
                ],
                '9.1'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getSourceBranchFromRefReturnsCorrectValueDataProvider
     */
    public function getSourceBranchFromRefReturnsCorrectValue(array $payload, string $expected)
    {
        $subject = new PushEvent(json_encode($payload));
        $this->assertEquals($expected, $subject->getBranchName());
    }

    public function getTargetBranchNameReturnsCorrectValueDataProvider()
    {
        return [
            'master' => [
                [
                    'ref' => 'refs/heads/master',
                ],
                'master'
            ],
            'TYPO3_8-7' => [
                [
                    'ref' => 'refs/heads/TYPO3_8-7',
                ],
                '8.7'
            ],
            '9.1' => [
                [
                    'ref' => 'refs/heads/9.1',
                ],
                '9.1'
            ],
            '10.2' => [
                [
                    'ref' => 'refs/heads/10.2',
                ],
                '10.2'
            ],
            '23.42' => [
                [
                    'ref' => 'refs/heads/23.42',
                ],
                '23.42'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getTargetBranchNameReturnsCorrectValueDataProvider
     */
    public function getTargetBranchNameReturnsCorrectValue(array $payload, string $expected)
    {
        $subject = new PushEvent(json_encode($payload));
        $this->assertEquals($expected, $subject->getTargetBranch());
    }

    /**
     * @test
     */
    public function getTargetBranchNameThrowsExceptionWithUnknownBranch()
    {
        $this->expectException(DoNotCareException::class);
        $subject = new PushEvent(json_encode(['ref' => 'refs/heads/foo']));
        $subject->getTargetBranch();
    }
}
