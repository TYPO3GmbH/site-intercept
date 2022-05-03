<?php

namespace App\Tests\Unit\Service;

use App\Client\GeneralClient;
use App\Exception\DocsNotValidException;
use App\Extractor\ComposerJson;
use App\Extractor\PushEvent;
use App\Service\DocumentationValidationService;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;

class DocumentationValidationServiceTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    public function approvesFullDocumentation(): void
    {
        $generalClientProphecy = $this->getGeneralClientProphecy([
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/README.rst', __DIR__ . '/Fixtures/File_Readme.rst'],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Settings.cfg', __DIR__ . '/Fixtures/File_Documentation_Settings.cfg'],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Index.rst', __DIR__ . '/Fixtures/File_Documentation_Index.rst'],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Includes.rst.txt', __DIR__ . '/Fixtures/File_Documentation_Includes.rst.txt'],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Sitemap.rst', __DIR__ . '/Fixtures/File_Documentation_Sitemap.rst'],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/genindex.rst', __DIR__ . '/Fixtures/File_Documentation_Genindex.rst'],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/_make/Makefile', ''],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Sitemap/Index.rst', ''],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Includes.txt', ''],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Settings.yml', ''],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Targets.rst', ''],
        ]);
        $pushEvent = new PushEvent(
            'https://github.com/FriendsOfTYPO3/extension_builder.git',
            'master',
            'https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/{file}'
        );
        $composerJson = new ComposerJson(['type' => 'typo3-cms-extension']);
        $subject = new DocumentationValidationService($generalClientProphecy->reveal());
        $subject->validate($pushEvent, $composerJson);
        // Plain assertion, we ensure that no exception is thrown
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function approvesSingleFileDocumentation(): void
    {
        $generalClientProphecy = $this->getGeneralClientProphecy([
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/README.rst', __DIR__ . '/Fixtures/File_Readme.rst'],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Settings.cfg', __DIR__ . '/Fixtures/File_Documentation_Settings.cfg'],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Index.rst', ''],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Includes.rst.txt', ''],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Sitemap.rst', ''],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/genindex.rst', ''],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/_make/Makefile', ''],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Sitemap/Index.rst', ''],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Includes.txt', ''],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Settings.yml', ''],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Targets.rst', ''],
        ]);
        $pushEvent = new PushEvent(
            'https://github.com/FriendsOfTYPO3/extension_builder.git',
            'master',
            'https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/{file}'
        );
        $composerJson = new ComposerJson(['type' => 'typo3-cms-extension']);
        $subject = new DocumentationValidationService($generalClientProphecy->reveal());
        $subject->validate($pushEvent, $composerJson);
        // Plain assertion, we ensure that no exception is thrown
        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function rejectsDocumentationDueToMissingFiles(): void
    {
        $this->expectException(DocsNotValidException::class);
        $this->expectExceptionCode(1651671307);
        $this->expectExceptionMessage('Settings.cfg is missing in Documentation/.');

        $generalClientProphecy = $this->getGeneralClientProphecy([
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/README.rst', __DIR__ . '/Fixtures/File_Readme.rst'],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Settings.cfg', ''],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Index.rst', ''],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Includes.rst.txt', ''],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Sitemap.rst', ''],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/genindex.rst', ''],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/_make/Makefile', ''],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Sitemap/Index.rst', ''],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Includes.txt', ''],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Settings.yml', ''],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Targets.rst', ''],
        ]);
        $pushEvent = new PushEvent(
            'https://github.com/FriendsOfTYPO3/extension_builder.git',
            'master',
            'https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/{file}'
        );
        $composerJson = new ComposerJson(['type' => 'typo3-cms-extension']);
        $subject = new DocumentationValidationService($generalClientProphecy->reveal());
        $subject->validate($pushEvent, $composerJson);
    }

    /**
     * @test
     */
    public function rejectsDocumentationDueToOutdatedFiles(): void
    {
        $this->expectException(DocsNotValidException::class);
        $this->expectExceptionCode(1651671307);
        $this->expectExceptionMessage('These files are outdated and should be removed: Documentation/_make/Makefile, Documentation/Sitemap/Index.rst, Documentation/Includes.txt, Documentation/Settings.yml, Documentation/Targets.rst.');

        $generalClientProphecy = $this->getGeneralClientProphecy([
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/README.rst', __DIR__ . '/Fixtures/File_Readme.rst'],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Settings.cfg', __DIR__ . '/Fixtures/File_Documentation_Settings.cfg'],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Index.rst', ''],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Includes.rst.txt', ''],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Sitemap.rst', ''],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/genindex.rst', ''],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/_make/Makefile', __DIR__ . '/Fixtures/File_Dummy'],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Sitemap/Index.rst', __DIR__ . '/Fixtures/File_Dummy'],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Includes.txt', __DIR__ . '/Fixtures/File_Dummy'],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Settings.yml', __DIR__ . '/Fixtures/File_Dummy'],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Targets.rst', __DIR__ . '/Fixtures/File_Dummy'],
        ]);
        $pushEvent = new PushEvent(
            'https://github.com/FriendsOfTYPO3/extension_builder.git',
            'master',
            'https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/{file}'
        );
        $composerJson = new ComposerJson(['type' => 'typo3-cms-extension']);
        $subject = new DocumentationValidationService($generalClientProphecy->reveal());
        $subject->validate($pushEvent, $composerJson);
    }

    /**
     * @test
     */
    public function rejectsDocumentationDueToBadContent(): void
    {
        $this->expectException(DocsNotValidException::class);
        $this->expectExceptionCode(1651671307);
        $this->expectExceptionMessage('
- README file misses badges with store statistics.
- README file misses badges with TYPO3 compatibility.
- README file misses link to project page on extensions.typo3.org (TER).
- Documentation/Settings.cfg contains outdated properties: t3author, description, github_commit_hash, github_revision_msg, github_sphinx_locale, t3core.
- Documentation/Settings.cfg misses proper values for properties: project_home, project_contact, project_repository, project_issues.
- Documentation/Index.rst misses including the /Includes.rst.txt.
- Documentation/Index.rst contains outdated fields: Description, Keywords, Copyright, Classification.
- Documentation/Index.rst misses the fields: Extension key, Package name.
- Documentation/Index.rst misses the table of contents.
- Includes.rst.txt is missing in Documentation/.
- Sitemap.rst is missing in Documentation/.
- genindex.rst is missing in Documentation/.');

        $generalClientProphecy = $this->getGeneralClientProphecy([
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/README.rst', __DIR__ . '/Fixtures/File_Readme_Invalid.rst'],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Settings.cfg', __DIR__ . '/Fixtures/File_Documentation_Settings_Invalid.cfg'],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Index.rst', __DIR__ . '/Fixtures/File_Documentation_Index_Invalid.rst'],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Includes.rst.txt', ''],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Sitemap.rst', ''],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/genindex.rst', ''],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/_make/Makefile', ''],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Sitemap/Index.rst', ''],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Includes.txt', ''],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Settings.yml', ''],
            ['https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/Documentation/Targets.rst', ''],
        ]);
        $pushEvent = new PushEvent(
            'https://github.com/FriendsOfTYPO3/extension_builder.git',
            'master',
            'https://raw.githubusercontent.com/FriendsOfTYPO3/extension_builder/master/{file}'
        );
        $composerJson = new ComposerJson(['type' => 'typo3-cms-extension']);
        $subject = new DocumentationValidationService($generalClientProphecy->reveal());
        $subject->validate($pushEvent, $composerJson);
    }

    private function getGeneralClientProphecy(array $requests): ObjectProphecy
    {
        $generalClientProphecy = $this->prophesize(GeneralClient::class);
        foreach ($requests as $request) {
            $generalClientProphecy
                ->request('GET', $request[0])
                ->shouldBeCalled()
                ->willReturn(new Response(
                    !empty($request[1]) ? 200 : 404, [],
                    !empty($request[1]) ? file_get_contents($request[1]) : null)
                );
        }
        return $generalClientProphecy;
    }
}
