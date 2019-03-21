<?php
declare(strict_types = 1);
namespace App\Tests\Unit\Extractor;

use App\Entity\DocsServerRedirect;
use App\Repository\DocsServerRedirectRepository;
use App\Service\DocsServerNginxService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class DocsNginxRedirectServiceTest extends TestCase
{
    /**
     * @var DocsServerNginxService
     */
    private $subject;

    public function setUp()
    {
        parent::setUp();
        $redirectRepositoryProphecy = $this->prophesize(DocsServerRedirectRepository::class);
        $redirectRepositoryProphecy->findAll()->willReturn([
            (new DocsServerRedirect())
                ->setId(1)
                ->setSource('/p/vendor/packageOld/1.0/Foo.html')
                ->setTarget('/p/vendor/packageNew/1.0/Foo.html')
                ->setCreatedAt(new \DateTime('2019-03-21 13:00:00'))
                ->setUpdatedAt(new \DateTime('2019-03-21 13:00:00'))
                ->setStatusCode(303),
            (new DocsServerRedirect())
                ->setId(2)
                ->setSource('/p/vendor/packageOld/2.0/Foo.html')
                ->setTarget('/p/vendor/packageNew/2.0/Foo.html')
                ->setCreatedAt(new \DateTime('2019-03-20 13:00:00'))
                ->setUpdatedAt(new \DateTime('2019-03-20 13:00:00'))
                ->setStatusCode(301),
        ]);
        $kernelProphecy = $this->prophesize(KernelInterface::class);
        $kernelProphecy->getCacheDir()->willReturn('/tmp/');
        $this->subject = new DocsServerNginxService(
            $redirectRepositoryProphecy->reveal(),
            $kernelProphecy->reveal(),
            new Filesystem()
        );
    }

    /**
     * @test
     */
    public function createRedirectConfigFileCreatesAValidConfigFile()
    {
        $filename = $this->subject->createRedirectConfigFile();
        $fileContent = file_get_contents($filename);

        $this->assertNotEmpty($filename);
        $this->assertFileExists($filename);
        $this->assertContains('# Rule: 1 | Created: 21.03.2019 13:00 | Updated: 21.03.2019 13:00', $fileContent);
        $this->assertContains('location = /p/vendor/packageOld/1.0/Foo.html {', $fileContent);
        $this->assertContains('return 303 https://$host/p/vendor/packageNew/1.0/Foo.html;', $fileContent);

        $this->assertContains('# Rule: 2 | Created: 20.03.2019 13:00 | Updated: 20.03.2019 13:00', $fileContent);
        $this->assertContains('location = /p/vendor/packageOld/2.0/Foo.html {', $fileContent);
        $this->assertContains('return 301 https://$host/p/vendor/packageNew/2.0/Foo.html;', $fileContent);
    }

    /**
     * @test
     */
    public function getFileContent()
    {
        $filename = $this->subject->createRedirectConfigFile();
        $fileContent = $this->subject->getFileContent(basename($filename));

        $this->assertContains('# Rule: 1 | Created: 21.03.2019 13:00 | Updated: 21.03.2019 13:00', $fileContent);
        $this->assertContains('location = /p/vendor/packageOld/1.0/Foo.html {', $fileContent);
        $this->assertContains('return 303 https://$host/p/vendor/packageNew/1.0/Foo.html;', $fileContent);

        $this->assertContains('# Rule: 2 | Created: 20.03.2019 13:00 | Updated: 20.03.2019 13:00', $fileContent);
        $this->assertContains('location = /p/vendor/packageOld/2.0/Foo.html {', $fileContent);
        $this->assertContains('return 301 https://$host/p/vendor/packageNew/2.0/Foo.html;', $fileContent);
    }
}
