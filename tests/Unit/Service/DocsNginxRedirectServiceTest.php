<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Tests\Unit\Service;

use App\Entity\DocsServerRedirect;
use App\Repository\DocsServerRedirectRepository;
use App\Service\DocsServerNginxService;
use DateTime;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Filesystem\Filesystem;

class DocsNginxRedirectServiceTest extends TestCase
{
    use ProphecyTrait;

    private DocsServerNginxService $subject;

    public function setUp(): void
    {
        parent::setUp();
        $redirectRepositoryProphecy = $this->prophesize(DocsServerRedirectRepository::class);
        $redirectRepositoryProphecy->findAll()->willReturn([
            (new DocsServerRedirect())
                ->setId(1)
                ->setSource('/p/vendor/packageOld/1.0/Foo.html')
                ->setTarget('/p/vendor/packageNew/1.0/Foo.html')
                ->setCreatedAt(new DateTime('2019-03-21 13:00:00'))
                ->setUpdatedAt(new DateTime('2019-03-21 13:00:00'))
                ->setStatusCode(303),
            (new DocsServerRedirect())
                ->setId(2)
                ->setSource('/p/vendor/packageOld/2.0/Foo.html')
                ->setTarget('/p/vendor/packageNew/2.0/Foo.html')
                ->setCreatedAt(new DateTime('2019-03-20 13:00:00'))
                ->setUpdatedAt(new DateTime('2019-03-20 13:00:00'))
                ->setStatusCode(302),
            (new DocsServerRedirect())
                ->setId(3)
                ->setSource('/typo3cms/extensions/packageOld/1.0/')
                ->setTarget('/p/vendor/packageOld/1.0/')
                ->setCreatedAt(new DateTime('2019-03-21 13:00:00'))
                ->setUpdatedAt(new DateTime('2019-03-21 13:00:00'))
                ->setStatusCode(303)
                ->setIsLegacy(true),
        ]);
        $this->subject = new DocsServerNginxService(
            $redirectRepositoryProphecy->reveal(),
            new Filesystem(),
            '/tmp/',
            '/tmp/'
        );
    }

    /**
     * @test
     */
    public function getDynamicConfigurationCreatesValidConfig(): void
    {
        $fileContent = implode(chr(10), $this->subject->getDynamicConfiguration());

        $this->assertStringContainsString('# Rule: 1 | Created: 21.03.2019 13:00 | Updated: 21.03.2019 13:00', $fileContent);
        $this->assertStringContainsString('location = /p/vendor/packageOld/1.0/Foo.html {', $fileContent);
        $this->assertStringContainsString('return 303 /p/vendor/packageNew/1.0/Foo.html;', $fileContent);

        $this->assertStringContainsString('# Rule: 2 | Created: 20.03.2019 13:00 | Updated: 20.03.2019 13:00', $fileContent);
        $this->assertStringContainsString('location = /p/vendor/packageOld/2.0/Foo.html {', $fileContent);
        $this->assertStringContainsString('return 302 /p/vendor/packageNew/2.0/Foo.html;', $fileContent);

        $this->assertStringContainsString('# Rule: 3 | Created: 21.03.2019 13:00 | Updated: 21.03.2019 13:00 | Legacy', $fileContent);
        $this->assertStringContainsString('location ~ ^/typo3cms/extensions/packageOld/1.0/(.*) {', $fileContent);
        $this->assertStringContainsString('return 303 /p/vendor/packageOld/1.0/$1;', $fileContent);
    }
}
