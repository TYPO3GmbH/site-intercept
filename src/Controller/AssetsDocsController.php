<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Controller;

use App\Repository\DocumentationJarRepository;
use App\Utility\DocsUtility;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Provides actions to generate "static" assets used by docs and TER
 */
class AssetsDocsController extends AbstractController
{
    private DocumentationJarRepository $documentationJarRepository;

    public function __construct(DocumentationJarRepository $documentationJarRepository)
    {
        $this->documentationJarRepository = $documentationJarRepository;
    }

    /**
     * @Route("/assets/docs/manuals.json", name="docs_assets_manuals")
     *
     * @return Response
     */
    public function manuals(): Response
    {
        $aggregatedExtensions = [];

        $legacyExtensions = json_decode(file_get_contents(__DIR__ . '/../../config/docs-legacy-rendering/typo3cms-extensions.json'), true, 512, JSON_THROW_ON_ERROR);
        foreach ($legacyExtensions as $extension) {
            $extensionKey = $extension['key'];
            $aggregatedExtensions[$extensionKey]['docs'] = [];
            foreach ($extension['paths'] as $majorMinorVersion => $path) {
                $majorMinorPatchVersion = explode('/', $path);
                if (empty($majorMinorPatchVersion[4])) {
                    continue;
                }
                $majorMinorPatchVersion = $majorMinorPatchVersion[4];
                $aggregatedExtensions[$extensionKey]['docs'][$majorMinorVersion] = [
                    'url' => 'https://docs.typo3.org' . $path,
                ];
                $aggregatedExtensions[$extensionKey]['docs'][$majorMinorPatchVersion] = [
                    'url' => 'https://docs.typo3.org' . $path,
                ];
            }
        }

        $extensions = $this->documentationJarRepository->findAvailableCommunityExtensions();

        foreach ($extensions as $extension) {
            if (!isset($aggregatedExtensions[$extension->getExtensionKey()])) {
                $aggregatedExtensions[$extension->getExtensionKey()] = [
                    'packageName' => $extension->getPackageName(),
                    'docs' => [],
                ];
            }
            if (!isset($aggregatedExtensions[$extension->getExtensionKey()]['packageName'])) {
                $aggregatedExtensions[$extension->getExtensionKey()]['packageName'] = $extension->getPackageName();
            }

            $aggregatedExtensions[$extension->getExtensionKey()]['docs'][$extension->getBranch()] = [
                'url' => DocsUtility::generateLinkToDocs($extension),
                'rendered' => $extension->getLastRenderedAt()->format(\DateTimeInterface::ATOM),
            ];

            $aggregatedExtensions[$extension->getExtensionKey()]['docs'][$extension->getTargetBranchDirectory()] = [
                'url' => DocsUtility::generateLinkToDocs($extension),
                'rendered' => $extension->getLastRenderedAt()->format(\DateTimeInterface::ATOM),
            ];
        }

        return JsonResponse::create($aggregatedExtensions);
    }

    /**
     * @Route("/assets/docs/extensions.js", name="docs_assets_extensions")
     *
     * @return Response
     */
    public function extensions(): Response
    {
        $extensions = $this->documentationJarRepository->findAllAvailableExtensions();

        $template = implode("\r\n", [
            '// This file has been automatically generated on %s',
            '// DO NOT MODIFY THIS FILE',
            'var extensionList = %s;',
        ]);

        $legacyExtensions = json_decode(file_get_contents(__DIR__ . '/../../config/docs-legacy-rendering/typo3cms-extensions.json'), true, 512, JSON_THROW_ON_ERROR);
        $flatList = $legacyExtensions;

        foreach ($extensions as $extension) {
            // We use the extension key (not package name) as array key here to have an easier game with legacy renderings
            $path = '/' . $extension->getTypeShort() . '/' . $extension->getPackageName() . '/' . $extension->getTargetBranchDirectory() . '/en-us';
            if (!isset($flatList[$extension->getExtensionKey()])) {
                // A new extension not yet in legacy list
                $flatList[$extension->getExtensionKey()] = [
                    'key' => $extension->getPackageName(),
                    'extensionKey' => $extension->getExtensionKey(),
                    'latest' => null, // this will be set later
                    'versions' => [
                        $extension->getTargetBranchDirectory() => $extension->getTargetBranchDirectory()
                    ],
                    'paths' => [
                        $extension->getTargetBranchDirectory() => $path,
                    ],
                ];
            } else {
                // New rendering has a fresh version for this 'branch', or this is a new branch
                $flatList[$extension->getExtensionKey()]['versions'][$extension->getTargetBranchDirectory()] = $extension->getTargetBranchDirectory();
                $flatList[$extension->getExtensionKey()]['paths'][$extension->getTargetBranchDirectory()] = $path;
                // Update packageName if possible, so 'key' is now packageName, and only 'extensionKey' is extensionKey ... this was not possible with legacy rendering
                $flatList[$extension->getExtensionKey()]['key'] = $extension->getPackageName();
            }
        }

        // Sort versions
        foreach ($flatList as &$item) {
            natsort($item['versions']);

            // natsort() keeps the array keys, this is unwanted
            $item['versions'] = array_values(array_reverse($item['versions']));

            // As the items are sorted as expected now, we can safely set the latest stable version
            $stableVersions = array_values(array_filter($item['versions'], static fn (string $version): bool => preg_match('/\d+.\d+(.\d+)?/', $version) === 1));
            $item['latest'] = $stableVersions[0] ?? $item['versions'][0];

            // Create ['versions'] = ['version' => 'path']
            $versionsWithPath = [];
            foreach ($item['versions'] as $version) {
                $versionsWithPath[$version] = $item['paths'][$version];
            }
            $item['versions'] = $versionsWithPath;
            unset($item['paths']);
        }
        unset($item);

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $encoded = json_encode(array_values($flatList), JSON_THROW_ON_ERROR);

        $javaScript = sprintf($template, $now->format(\DateTimeInterface::ATOM), $encoded);

        return Response::create($javaScript, 200, ['Content-Type' => 'text/javascript']);
    }
}
