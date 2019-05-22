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
    /**
     * @var DocumentationJarRepository
     */
    private $documentationJarRepository;

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
        $extensions = $this->documentationJarRepository->findCommunityExtensions();
        $aggregatedExtensions = [];

        foreach ($extensions as $extension) {
            if (!isset($aggregatedExtensions[$extension->getExtensionKey()])) {
                $aggregatedExtensions[$extension->getExtensionKey()] = [
                    'packageName' => $extension->getPackageName(),
                    'docs' => [],
                ];
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
        $extensions = $this->documentationJarRepository->findAllExtensions();

        $template = implode("\r\n", [
            '// This file has been automatically generated on %s',
            '// DO NOT MODIFY THIS FILE',
            'var extensionList = %s;',
        ]);
        $flatList = [];

        foreach ($extensions as $extension) {
            if (!isset($flatList[$extension->getExtensionKey()])) {
                $flatList[$extension->getExtensionKey()] = [
                    'key' => $extension->getExtensionKey(),
                    'latest' => null, // this will be set later
                    'versions' => [$extension->getTargetBranchDirectory()],
                ];
            } else {
                $flatList[$extension->getExtensionKey()]['versions'][] = $extension->getTargetBranchDirectory();
            }
        }

        // Sort versions
        foreach ($flatList as &$item) {
            natsort($item['versions']);

            // natsort() keeps the array keys, this is unwanted
            $item['versions'] = array_values(array_reverse($item['versions']));

            // As the items are sorted as expected now, we can safely set the latest stable version
            $stableVersions = array_values(array_filter($item['versions'], static function (string $version): bool {
                // Remove any item not being a version number
                return preg_match('/\d+.\d+(.\d+)?/', $version) === 1;
            }));

            $item['latest'] = $stableVersions[0] ?? $item['versions'][0];
        }
        unset($item);

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $encoded = json_encode(array_values($flatList));

        $javaScript = sprintf($template, $now->format(\DateTimeInterface::ATOM), $encoded);

        return Response::create($javaScript, 200, ['Content-Type' => 'text/javascript']);
    }
}
