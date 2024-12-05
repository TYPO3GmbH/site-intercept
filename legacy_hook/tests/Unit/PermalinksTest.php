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
    private ServerRequestInterface&MockObject $requestMock;
    private UriInterface&MockObject $uriMock;

    private DocumentationLinker $subject;

    public function setUp(): void
    {
        parent::setUp();

        $this->requestMock = $this->getMockBuilder(ServerRequestInterface::class)->getMock();
        $this->uriMock = $this->getMockBuilder(UriInterface::class)->getMock();
        $this->requestMock->expects($this->any())
            ->method('getUri')
            ->willReturn($this->uriMock);
        $this->subject = new DocumentationLinker($this->requestMock);

        // See legacy_hook/tests/Unit/Fixtures/Permalinks/spider.sh to generate/update fixtures.
        $GLOBALS['_SERVER']['DOCUMENT_ROOT'] = __DIR__ . '/Fixtures/Permalinks';
    }

    public function tearDown(): void
    {
        unset($GLOBALS['_SERVER']['DOCUMENT_ROOT']);
        parent::tearDown();
    }

    public static function redirectFailsDataProvider(): array
    {
        return [
            'core manual, wrong anchor' => [
                'permalink' => 'changelog:important-4711',
            ],
            'missing inventory' => [
                'permalink' => 't3tsconfiguration:nothing',
            ],
            'missing composer package' => [
                'permalink' => 'georgringer-nonews:nothing',
            ],
            'missing repository' => [
                'permalink' => 'typo3-cms',
            ],
            'missing split repository' => [
                'permalink' => 'typo3-cms-seo',
            ],
            'only version' => [
                'permalink' => '@12.4',
            ],
            'only version, slash' => [
                'permalink' => '/@12.4',
            ],
            'only version, repo' => [
                'permalink' => 'repo:@12.4',
            ],
            'only version, multi repo' => [
                'permalink' => 'repo:repo:repo',
            ],
            'too many slashes' => [
                'permalink' => '../../../../../../../../../etc/passwd',
            ],
        ];
    }

    public static function redirectWorksDataProvider(): array
    {
        return [
            'core manual, no version' => [
                'permalink' => 'changelog:important-100889-1690476872',
                'location' => 'https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/11.5.x/Important-100889-AllowInsecureSiteResolutionByQueryParameters.html#important-100889-1690476872',
            ],
            'core manual, wrong number version (main fallback)' => [
                'permalink' => 'changelog:important-100889-1690476872@47.11',
                'location' => 'https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/11.5.x/Important-100889-AllowInsecureSiteResolutionByQueryParameters.html#important-100889-1690476872',
            ],
            'core manual, wrong named version (main fallback)' => [
                'permalink' => 'changelog:important-100889-1690476872@superunstable',
                'location' => 'https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/11.5.x/Important-100889-AllowInsecureSiteResolutionByQueryParameters.html#important-100889-1690476872',
            ],
            'core manual, with number version' => [
                'permalink' => 'changelog:important-100889-1690476872@13.4',
                'location' => 'https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/11.5.x/Important-100889-AllowInsecureSiteResolutionByQueryParameters.html#important-100889-1690476872',
            ],

            'core cms-package manual, no version' => [
                'permalink' => 'typo3-cms-seo:developer',
                'location' => 'https://docs.typo3.org/c/typo3/cms-seo/main/en-us/Developer/Index.html#developer',
            ],
            'core cms-package manual, with number version' => [
                'permalink' => 'typo3-cms-seo:developer@13.4',
                'location' => 'https://docs.typo3.org/c/typo3/cms-seo/13.4/en-us/Developer/Index.html#developer',
            ],
            'core cms-package manual, with stable version' => [
                'permalink' => 'typo3-cms-seo:developer@stable',
                'location' => 'https://docs.typo3.org/c/typo3/cms-seo/13.4/en-us/Developer/Index.html#developer',
            ],
            'core cms-package manual, with oldstable version' => [
                'permalink' => 'typo3-cms-seo:developer@oldstable',
                'location' => 'https://docs.typo3.org/c/typo3/cms-seo/12.4/en-us/Developer/Index.html#developer',
            ],
            'core cms-package manual, with dev version' => [
                'permalink' => 'typo3-cms-seo:developer@dev',
                'location' => 'https://docs.typo3.org/c/typo3/cms-seo/main/en-us/Developer/Index.html#developer',
            ],

            'official "other" manual, no version' => [
                'permalink' => 't3viewhelper:typo3fluid-fluid-comment',
                'location' => 'https://docs.typo3.org/other/typo3/view-helper-reference/main/en-us/Global/Comment.html#typo3fluid-fluid-comment',
            ],
            'official "other" manual, main version' => [
                'permalink' => 't3viewhelper:typo3fluid-fluid-comment@main',
                'location' => 'https://docs.typo3.org/other/typo3/view-helper-reference/main/en-us/Global/Comment.html#typo3fluid-fluid-comment',
            ],
            'official "other" manual, 13.4 version' => [
                'permalink' => 't3viewhelper:typo3fluid-fluid-comment@13.4',
                'location' => 'https://docs.typo3.org/other/typo3/view-helper-reference/13.4/en-us/Global/Comment.html#typo3fluid-fluid-comment',
            ],

            'official inventory manual, no version' => [
                'permalink' => 't3coreapi:security-introduction',
                'location' => 'https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/Security/Introduction/Index.html#security-introduction',
            ],
            'official inventory manual, main version' => [
                'permalink' => 't3coreapi:security-introduction@main',
                'location' => 'https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/Security/Introduction/Index.html#security-introduction',
            ],
            'official inventory manual, 13.4 version' => [
                'permalink' => 't3coreapi:security-introduction@13.4',
                'location' => 'https://docs.typo3.org/m/typo3/reference-coreapi/13.4/en-us/Security/Introduction/Index.html#security-introduction',
            ],

            'Other documentation, no version' => [
                'permalink' => 't3renderguides:migration',
                'location' => 'https://docs.typo3.org/other/t3docs/render-guides/main/en-us/Migration/Index.html#migration',
            ],
            'Other documentation, main version' => [
                'permalink' => 't3renderguides:migration@main',
                'location' => 'https://docs.typo3.org/other/t3docs/render-guides/main/en-us/Migration/Index.html#migration',
            ],
            'Other documentation, 0.19 version' => [
                'permalink' => 't3renderguides:migration@0.19',
                # Internally always referred to for "main"!
                'location' => 'https://docs.typo3.org/other/t3docs/render-guides/main/en-us/Migration/Index.html#migration',
            ],
            'Other documentation, wrong numbered version' => [
                'permalink' => 't3renderguides:migration@13.4',
                # Internally always referred to for "main"!
                'location' => 'https://docs.typo3.org/other/t3docs/render-guides/main/en-us/Migration/Index.html#migration',
            ],

            'Third party docs, no version' => [
                'permalink' => 'georgringer-news:reference',
                'location' => 'https://docs.typo3.org/p/georgringer/news/main/en-us/Reference/Index.html#reference',
            ],
            'Third party docs, 12.1 version' => [
                'permalink' => 'georgringer-news:reference@12.1',
                'location' => 'https://docs.typo3.org/p/georgringer/news/12.1/en-us/Reference/Index.html#reference',
            ],
            'Third party docs, 10.0 version' => [
                'permalink' => 'georgringer-news:reference@10.0',
                'location' => 'https://docs.typo3.org/p/georgringer/news/10.0/en-us/Reference/Index.html#reference',
            ],
            'Third party docs, 10.0 version, underscores' => [
                'permalink' => 'georgringer-news:how_to_rewrite_urls@10.0',
                'location' => 'https://docs.typo3.org/p/georgringer/news/10.0/en-us/Tutorials/BestPractice/Routing/Index.html#how-to-rewrite-urls',
            ],

            // Soft-fail to main
            'core cms-package manual, wrong number version' => [
                'permalink' => 'typo3-cms-seo:developer@47.11',
                'location' => 'https://docs.typo3.org/c/typo3/cms-seo/main/en-us/Developer/Index.html#developer',
            ],
            'core cms-package manual, wrong named version' => [
                'permalink' => 'typo3-cms-seo:developer@superunstable',
                'location' => 'https://docs.typo3.org/c/typo3/cms-seo/main/en-us/Developer/Index.html#developer',
            ],

            # Confval example
            'confval tlo resolve' => [
                'permalink' => 't3tsref:tlo-module-properties-settings',
                'location' => 'https://docs.typo3.org/m/typo3/reference-typoscript/main/en-us/TopLevelObjects/Module.html#tlo-module-properties-settings',
            ],

            'confval resolve' => [
                'permalink' => 't3tsref:confval-module-settings',
                'location' => 'https://docs.typo3.org/m/typo3/reference-typoscript/main/en-us/TopLevelObjects/Module.html#confval-module-settings',
            ],

            # Lower/Uppercase normalization
            'Other documentation, no version, UPPER/lower casing' => [
                'permalink' => 'T3RENDERGUIDES:ajaxversions-data-attributes',
                'location' => 'https://docs.typo3.org/other/t3docs/render-guides/main/en-us/Developer/AjaxVersions.html#AjaxVersions-data-attributes',
            ],
            'Other documentation, main version, UPPER/lower casing' => [
                'permalink' => 't3renderguides:AJAXversions-data-attributes@MAIN',
                'location' => 'https://docs.typo3.org/other/t3docs/render-guides/main/en-us/Developer/AjaxVersions.html#AjaxVersions-data-attributes',
            ],
            'Other documentation, 0.19 version, UPPER/lower casing' => [
                'permalink' => 't3RENDERguides:AjaxVersions-Data-Attributes@0.19',
                'location' => 'https://docs.typo3.org/other/t3docs/render-guides/main/en-us/Developer/AjaxVersions.html#AjaxVersions-data-attributes',
            ],
            'Other documentation, wrong numbered version, UPPER/lower casing' => [
                'permalink' => 't3renderguides:ajaxversions-data-attributes@13.4',
                'location' => 'https://docs.typo3.org/other/t3docs/render-guides/main/en-us/Developer/AjaxVersions.html#AjaxVersions-data-attributes',
            ],
            'official "other" manual, no version, UPPER/lower case' => [
                'permalink' => 't3VIEWhelper:TYPO3fluid-fluid-comment',
                'location' => 'https://docs.typo3.org/other/typo3/view-helper-reference/main/en-us/Global/Comment.html#typo3fluid-fluid-comment',
            ],
            'official "other" manual, main version, UPPER/lower case' => [
                'permalink' => 'T3VIEWHELPER:tyPO3fluid-FLuid-Comment@MaIn',
                'location' => 'https://docs.typo3.org/other/typo3/view-helper-reference/main/en-us/Global/Comment.html#typo3fluid-fluid-comment',
            ],
            'official "other" manual, 13.4 version, UPPER/lower case' => [
                'permalink' => 't3viewhelper:typO3fluid-FLUID-comment@13.4',
                'location' => 'https://docs.typo3.org/other/typo3/view-helper-reference/13.4/en-us/Global/Comment.html#typo3fluid-fluid-comment',
            ],
            'Third party docs, 10.0 version, underscores, UPPER/lower case' => [
                'permalink' => 'georgringer-NEWS:How_To_rEwrite_Urls@10.0',
                'location' => 'https://docs.typo3.org/p/georgringer/news/10.0/en-us/Tutorials/BestPractice/Routing/Index.html#how-to-rewrite-urls',
            ],
        ];
    }

    #[DataProvider('redirectWorksDataProvider')]
    #[Test]
    public function redirectWorksForPermalink(string $permalink, string $location): void
    {
        self::assertInstanceOf(DocumentationLinker::class, $this->subject);
        $describer = $this->subject->resolvePermalink($permalink);

        self::assertSame(307, $describer->statusCode);
        self::assertSame(['Location' => $location], $describer->headers);
        self::assertStringContainsString('Redirect to', $describer->body);
    }

    #[DataProvider('redirectFailsDataProvider')]
    #[Test]
    public function redirectFailsForPermalink(string $permalink): void
    {
        self::assertInstanceOf(DocumentationLinker::class, $this->subject);
        $describer = $this->subject->resolvePermalink($permalink);

        self::assertSame(404, $describer->statusCode);
        self::assertSame([], $describer->headers);
        self::assertStringContainsString('Invalid shortcode', $describer->body);
    }
}
