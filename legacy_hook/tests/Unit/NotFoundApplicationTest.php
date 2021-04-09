<?php
declare(strict_types = 1);


namespace App\Tests\Unit;

/*
 * This file is part of the package t3g/intercept-legacy-hook.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use App\NotFoundApplication;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use org\bovigo\vfs\vfsStream;

class NotFoundApplicationTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $requestMock;

    /**
     * @var MockObject
     */
    private $uriMock;

    /**
     * @var NotFoundApplication
     */
    private $subject;

    public function setUp(): void
    {
        parent::setUp();

        vfsStream::setup('root', null, [
            'site' => [
                '404.html' => 'default 404 page content',
                'm' => [
                    'typo3' => [
                        'team-t3docteam' => [
                            'master' => [
                                'en-us' => [
                                    'OverviewOfManuals' => [
                                        '404.html' => 'some more specific 404 page content',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $GLOBALS['_SERVER']['DOCUMENT_ROOT'] = vfsStream::url('root/site/');

        $this->requestMock = $this->getMockBuilder(ServerRequestInterface::class)->getMock();
        $this->uriMock = $this->getMockBuilder(UriInterface::class)->getMock();
        $this->requestMock->expects($this->any())
            ->method('getUri')
            ->willReturn($this->uriMock);
        $this->subject = new NotFoundApplication($this->requestMock);
    }

    public function tearDown(): void
    {
        unset($GLOBALS['_SERVER']['DOCUMENT_ROOT']);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function statusCodeIs404()
    {
        $this->configureRequestUriPath('');
        $response = $this->subject->handle();
        $this->assertSame(404, $response->getStatusCode(), 'Not found did not return 404 as status code.');
    }

    /**
     * @test
     */
    public function default404FileIsReturned()
    {
        $this->configureRequestUriPath('');
        $response = $this->subject->handle();
        $this->assertSame(
            'default 404 page content',
            (string) $response->getBody(),
            'Default 404 page content was not delivered.'
        );
    }

    /**
     * @test
     */
    public function specific404FileIsReturnedForDeeperLevelCall()
    {
        $this->configureRequestUriPath('/m/typo3/team-t3docteam/master/en-us/OverviewOfManuals/DoesNotExist/AnotherLevel/Index.html');

        $response = $this->subject->handle();
        $this->assertSame(
            'some more specific 404 page content',
            (string) $response->getBody(),
            'Specific 404 page content was not delivered.'
        );
    }

    /**
     * @test
     */
    public function default404FileIsReturnedForDeeperLevelCall()
    {
        $this->configureRequestUriPath('/m/typo3/team-t3docteam/master/en-us/');

        $response = $this->subject->handle();
        $this->assertSame(
            'default 404 page content',
            (string) $response->getBody(),
            'Default 404 page content was not delivered.'
        );
    }

    /**
     * @test
     */
    public function baseTagIsInserted()
    {
        $this->configureRequestUriPath('');
        vfsStream::setup('root', null, [
            'site' => [
                '404.html' => file_get_contents($this->getTestFile('404.html')),
            ],
        ]);

        $response = $this->subject->handle();
        $this->assertStringEqualsFile(
            $this->getTestFile('Expected404.html'),
            (string) $response->getBody(),
            'Modified 404 page content was not delivered.'
        );
    }

    private function configureRequestUriPath(string $path = '')
    {
        $this->uriMock->expects($this->any())
            ->method('getPath')
            ->willReturn($path);
    }

    private function getTestFile(string $fileName): string
    {
        return implode(DIRECTORY_SEPARATOR, [__DIR__, 'Fixtures', 'Files', $fileName]);
    }
}
