<?php
declare(strict_types = 1);

namespace App\Tests\Unit;

/*
 * This file is part of the package t3g/intercept-legacy-hook.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use App\DocumentationLinker;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

class PermalinksTest extends TestCase
{
    private DocumentationLinker $subject;

    public function setUp(): void
    {
        parent::setUp();

        $requestMock = $this->getMockBuilder(ServerRequestInterface::class)->getMock();
        $uriMock = $this->getMockBuilder(UriInterface::class)->getMock();
        $requestMock
            ->method('getUri')
            ->willReturn($uriMock);
        $this->subject = new DocumentationLinker($requestMock);

        // See legacy_hook/tests/Unit/Fixtures/Permalinks/spider.sh to generate/update fixtures.
        $GLOBALS['_SERVER']['DOCUMENT_ROOT'] = __DIR__ . '/Fixtures/Permalinks';
    }

    public function tearDown(): void
    {
        unset($GLOBALS['_SERVER']['DOCUMENT_ROOT']);
        parent::tearDown();
    }

    public static function redirectFailsDataProvider(): \Iterator
    {
        yield 'core manual, wrong anchor' => [
            'changelog:important-4711',
        ];
        yield 'missing inventory' => [
            't3tsconfiguration:nothing',
        ];
        yield 'missing composer package' => [
            'georgringer-nonews:nothing',
        ];
        yield 'missing repository' => [
            'typo3-cms',
        ];
        yield 'missing split repository' => [
            'typo3-cms-seo',
        ];
        yield 'only version' => [
            '@12.4',
        ];
        yield 'only version, slash' => [
            '/@12.4',
        ];
        yield 'only version, repo' => [
            'repo:@12.4',
        ];
        yield 'only version, multi repo' => [
            'repo:repo:repo',
        ];
        yield 'too many slashes' => [
            '../../../../../../../../../etc/passwd',
        ];
    }

    public static function redirectWorksDataProvider(): \Iterator
    {
        yield 'dupe sorting' => [
            'dummyvendor-dummy:dupe-entry',
            'https://docs.typo3.org/p/dummyvendor/dummy/main/en-us/Index.html#dupe-entry',
        ];
        yield 'core manual, no version' => [
            'changelog:important-100889-1690476872',
            'https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/11.5.x/Important-100889-AllowInsecureSiteResolutionByQueryParameters.html#important-100889-1690476872',
        ];
        yield 'core manual, wrong number version (main fallback)' => [
            'changelog:important-100889-1690476872@47.11',
            'https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/11.5.x/Important-100889-AllowInsecureSiteResolutionByQueryParameters.html#important-100889-1690476872',
        ];
        yield 'core manual, wrong named version (main fallback)' => [
            'changelog:important-100889-1690476872@superunstable',
            'https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/11.5.x/Important-100889-AllowInsecureSiteResolutionByQueryParameters.html#important-100889-1690476872',
        ];
        yield 'core manual, with number version' => [
            'changelog:important-100889-1690476872@13.4',
            'https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/11.5.x/Important-100889-AllowInsecureSiteResolutionByQueryParameters.html#important-100889-1690476872',
        ];
        yield 'core cms-package manual, no version' => [
            'typo3-cms-seo:developer',
            'https://docs.typo3.org/c/typo3/cms-seo/main/en-us/Developer/Index.html#developer',
        ];
        yield 'core cms-package manual, with number version' => [
            'typo3-cms-seo:developer@13.4',
            'https://docs.typo3.org/c/typo3/cms-seo/13.4/en-us/Developer/Index.html#developer',
        ];
        yield 'core cms-package manual, with stable version' => [
            'typo3-cms-seo:developer@stable',
            'https://docs.typo3.org/c/typo3/cms-seo/13.4/en-us/Developer/Index.html#developer',
        ];
        yield 'core cms-package manual, with oldstable version' => [
            'typo3-cms-seo:developer@oldstable',
            'https://docs.typo3.org/c/typo3/cms-seo/12.4/en-us/Developer/Index.html#developer',
        ];
        yield 'core cms-package manual, with dev version' => [
            'typo3-cms-seo:developer@dev',
            'https://docs.typo3.org/c/typo3/cms-seo/main/en-us/Developer/Index.html#developer',
        ];
        yield 'core manual, no cms- prefix, no version' => [
            'typo3-theme-camino:camino',
            'https://docs.typo3.org/c/typo3/theme-camino/main/en-us/Index.html#camino',
        ];
        yield 'core manual, no cms- prefix, with number version' => [
            'typo3-theme-camino:camino@12.4',
            'https://docs.typo3.org/c/typo3/theme-camino/12.4/en-us/Index.html#camino',
        ];
        yield 'core manual, no cms- prefix, with stable version' => [
            'typo3-theme-camino:camino@stable',
            'https://docs.typo3.org/c/typo3/theme-camino/13.4/en-us/Index.html#camino',
        ];
        yield 'core manual, no cms- prefix, with oldstable version' => [
            'typo3-theme-camino:camino@oldstable',
            'https://docs.typo3.org/c/typo3/theme-camino/12.4/en-us/Index.html#camino',
        ];
        yield 'core manual, no cms- prefix, with dev version' => [
            'typo3-theme-camino:camino@dev',
            'https://docs.typo3.org/c/typo3/theme-camino/main/en-us/Index.html#camino',
        ];
        yield 'official "other" manual, no version' => [
            't3viewhelper:typo3fluid-fluid-comment',
            'https://docs.typo3.org/other/typo3/view-helper-reference/main/en-us/Global/Comment.html#typo3fluid-fluid-comment',
        ];
        yield 'official "other" manual, main version' => [
            't3viewhelper:typo3fluid-fluid-comment@main',
            'https://docs.typo3.org/other/typo3/view-helper-reference/main/en-us/Global/Comment.html#typo3fluid-fluid-comment',
        ];
        yield 'official "other" manual, 13.4 version' => [
            't3viewhelper:typo3fluid-fluid-comment@13.4',
            'https://docs.typo3.org/other/typo3/view-helper-reference/13.4/en-us/Global/Comment.html#typo3fluid-fluid-comment',
        ];
        yield 'official inventory manual, no version' => [
            't3coreapi:security-introduction',
            'https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/Security/Introduction/Index.html#security-introduction',
        ];
        yield 'official inventory manual, main version' => [
            't3coreapi:security-introduction@main',
            'https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/Security/Introduction/Index.html#security-introduction',
        ];
        yield 'official inventory manual, 13.4 version' => [
            't3coreapi:security-introduction@13.4',
            'https://docs.typo3.org/m/typo3/reference-coreapi/13.4/en-us/Security/Introduction/Index.html#security-introduction',
        ];
        yield 'Other documentation, no version' => [
            't3renderguides:migration',
            'https://docs.typo3.org/other/t3docs/render-guides/main/en-us/Migration/Index.html#migration',
        ];
        yield 'Other documentation, main version' => [
            't3renderguides:migration@main',
            'https://docs.typo3.org/other/t3docs/render-guides/main/en-us/Migration/Index.html#migration',
        ];
        yield 'Other documentation, 0.19 version' => [
            't3renderguides:migration@0.19',
            # Internally always referred to for "main"!
            'https://docs.typo3.org/other/t3docs/render-guides/main/en-us/Migration/Index.html#migration',
        ];
        yield 'Other documentation, wrong numbered version' => [
            't3renderguides:migration@13.4',
            # Internally always referred to for "main"!
            'https://docs.typo3.org/other/t3docs/render-guides/main/en-us/Migration/Index.html#migration',
        ];
        yield 'Third party docs, no version' => [
            'georgringer-news:reference',
            'https://docs.typo3.org/p/georgringer/news/main/en-us/Reference/Index.html#reference',
        ];
        yield 'Third party docs, 12.1 version' => [
            'georgringer-news:reference@12.1',
            'https://docs.typo3.org/p/georgringer/news/12.1/en-us/Reference/Index.html#reference',
        ];
        yield 'Third party docs, 10.0 version' => [
            'georgringer-news:reference@10.0',
            'https://docs.typo3.org/p/georgringer/news/10.0/en-us/Reference/Index.html#reference',
        ];
        yield 'Third party docs, 10.0 version, underscores' => [
            'georgringer-news:how_to_rewrite_urls@10.0',
            'https://docs.typo3.org/p/georgringer/news/10.0/en-us/Tutorials/BestPractice/Routing/Index.html#how-to-rewrite-urls',
        ];
        // Soft-fail to main
        yield 'core cms-package manual, wrong number version' => [
            'typo3-cms-seo:developer@47.11',
            'https://docs.typo3.org/c/typo3/cms-seo/main/en-us/Developer/Index.html#developer',
        ];
        yield 'core cms-package manual, wrong named version' => [
            'typo3-cms-seo:developer@superunstable',
            'https://docs.typo3.org/c/typo3/cms-seo/main/en-us/Developer/Index.html#developer',
        ];
        # Confval example
        yield 'confval tlo resolve' => [
            't3tsref:tlo-module-properties-settings',
            'https://docs.typo3.org/m/typo3/reference-typoscript/main/en-us/TopLevelObjects/Module.html#tlo-module-properties-settings',
        ];
        yield 'confval resolve' => [
            't3tsref:module-settings',
            'https://docs.typo3.org/m/typo3/reference-typoscript/main/en-us/TopLevelObjects/Module.html#confval-module-settings',
        ];
        yield 'confval resolve (fallback)' => [
            't3tsref:confval-module-settings',
            'https://docs.typo3.org/m/typo3/reference-typoscript/main/en-us/TopLevelObjects/Module.html#confval-module-settings',
        ];
        yield 'confval-menu resolve' => [
            't3coreapi:backend-module',
            'https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/Backend/BackendModules/ModuleConfiguration/Index.html#confval-menu-backend-module',
        ];
        yield 'confval-menu resolve (fallback)' => [
            't3coreapi:confval-menu-backend-module',
            'https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/Backend/BackendModules/ModuleConfiguration/Index.html#confval-menu-backend-module',
        ];
        # console command example
        yield 'console command list' => [
            't3coreapi:apioverview-commandcontrollers-listcommands',
            'https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/CommandControllers/ListCommands.html#console-command-list-apioverview-commandcontrollers-listcommands',
        ];
        yield 'console command fallback' => [
            't3coreapi:console-command-list-apioverview-commandcontrollers-listcommands',
            'https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/CommandControllers/ListCommands.html#console-command-list-apioverview-commandcontrollers-listcommands',
        ];
        yield 'console command item' => [
            't3coreapi:completion',
            'https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/CommandControllers/ListCommands.html#console-command-completion',
        ];
        yield 'console command item fallback' => [
            't3coreapi:console-command-completion',
            'https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ApiOverview/CommandControllers/ListCommands.html#console-command-completion',
        ];
        # Lower/Uppercase normalization
        yield 'Other documentation, no version, UPPER/lower casing' => [
            'T3RENDERGUIDES:ajaxversions-data-attributes',
            'https://docs.typo3.org/other/t3docs/render-guides/main/en-us/Developer/AjaxVersions.html#AjaxVersions-data-attributes',
        ];
        yield 'Other documentation, main version, UPPER/lower casing' => [
            't3renderguides:AJAXversions-data-attributes@MAIN',
            'https://docs.typo3.org/other/t3docs/render-guides/main/en-us/Developer/AjaxVersions.html#AjaxVersions-data-attributes',
        ];
        yield 'Other documentation, 0.19 version, UPPER/lower casing' => [
            't3RENDERguides:AjaxVersions-Data-Attributes@0.19',
            'https://docs.typo3.org/other/t3docs/render-guides/main/en-us/Developer/AjaxVersions.html#AjaxVersions-data-attributes',
        ];
        yield 'Other documentation, wrong numbered version, UPPER/lower casing' => [
            't3renderguides:ajaxversions-data-attributes@13.4',
            'https://docs.typo3.org/other/t3docs/render-guides/main/en-us/Developer/AjaxVersions.html#AjaxVersions-data-attributes',
        ];
        yield 'official "other" manual, no version, UPPER/lower case' => [
            't3VIEWhelper:TYPO3fluid-fluid-comment',
            'https://docs.typo3.org/other/typo3/view-helper-reference/main/en-us/Global/Comment.html#typo3fluid-fluid-comment',
        ];
        yield 'official "other" manual, main version, UPPER/lower case' => [
            'T3VIEWHELPER:tyPO3fluid-FLuid-Comment@MaIn',
            'https://docs.typo3.org/other/typo3/view-helper-reference/main/en-us/Global/Comment.html#typo3fluid-fluid-comment',
        ];
        yield 'official "other" manual, 13.4 version, UPPER/lower case' => [
            't3viewhelper:typO3fluid-FLUID-comment@13.4',
            'https://docs.typo3.org/other/typo3/view-helper-reference/13.4/en-us/Global/Comment.html#typo3fluid-fluid-comment',
        ];
        yield 'Third party docs, 10.0 version, underscores, UPPER/lower case' => [
            'georgringer-NEWS:How_To_rEwrite_Urls@10.0',
            'https://docs.typo3.org/p/georgringer/news/10.0/en-us/Tutorials/BestPractice/Routing/Index.html#how-to-rewrite-urls',
        ];
        # slasher
        yield 'core cms-package manual, no version, slash syntax' => [
            'typo3/cms-seo:developer',
            'https://docs.typo3.org/c/typo3/cms-seo/main/en-us/Developer/Index.html#developer',
        ];
        yield 'core cms-package manual, with number version, slash syntax' => [
            'typo3/cms-seo:developer@13.4',
            'https://docs.typo3.org/c/typo3/cms-seo/13.4/en-us/Developer/Index.html#developer',
        ];
        yield 'core cms-package manual, with stable version, slash syntax' => [
            'typo3/cms-seo:developer@stable',
            'https://docs.typo3.org/c/typo3/cms-seo/13.4/en-us/Developer/Index.html#developer',
        ];
        yield 'core cms-package manual, with oldstable version, slash syntax' => [
            'typo3/cms-seo:developer@oldstable',
            'https://docs.typo3.org/c/typo3/cms-seo/12.4/en-us/Developer/Index.html#developer',
        ];
        yield 'core cms-package manual, with dev version, slash syntax' => [
            'typo3/cms-seo:developer@dev',
            'https://docs.typo3.org/c/typo3/cms-seo/main/en-us/Developer/Index.html#developer',
        ];
        yield 'Third party docs, no version, slash syntax' => [
            'georgringer/news:reference',
            'https://docs.typo3.org/p/georgringer/news/main/en-us/Reference/Index.html#reference',
        ];
        yield 'Third party docs, 12.1 version, slash syntax' => [
            'georgringer/news:reference@12.1',
            'https://docs.typo3.org/p/georgringer/news/12.1/en-us/Reference/Index.html#reference',
        ];
        yield 'Third party docs, 10.0 version, slash syntax' => [
            'georgringer/news:reference@10.0',
            'https://docs.typo3.org/p/georgringer/news/10.0/en-us/Reference/Index.html#reference',
        ];
        yield 'Third party docs, 10.0 version, underscores, slash syntax' => [
            'georgringer/news:how_to_rewrite_urls@10.0',
            'https://docs.typo3.org/p/georgringer/news/10.0/en-us/Tutorials/BestPractice/Routing/Index.html#how-to-rewrite-urls',
        ];
    }

    #[DataProvider('redirectWorksDataProvider')]
    #[Test]
    public function redirectWorksForPermalink(string $permalink, string $location): void
    {
        $this->assertInstanceOf(DocumentationLinker::class, $this->subject);
        $describer = $this->subject->resolvePermalink($permalink);

        $this->assertSame(307, $describer->statusCode, 'Header mismatch: ' . $describer->body);
        $this->assertSame(['Location' => $location], $describer->headers);
        $this->assertStringContainsString('Redirect to', $describer->body);
    }

    #[DataProvider('redirectFailsDataProvider')]
    #[Test]
    public function redirectFailsForPermalink(string $permalink): void
    {
        $this->assertInstanceOf(DocumentationLinker::class, $this->subject);
        $describer = $this->subject->resolvePermalink($permalink);

        $this->assertSame(404, $describer->statusCode);
        $this->assertSame([], $describer->headers);
        $this->assertStringContainsString('Invalid shortcode', $describer->body);
    }
}
