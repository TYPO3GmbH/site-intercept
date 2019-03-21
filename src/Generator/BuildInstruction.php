<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Generator;

use App\Client\GeneralClient;
use App\Entity\DeploymentInformation;
use App\Entity\DocumentationJar;
use App\Extractor\GithubPushEventForDocs;
use App\Kernel;
use App\Repository\DocumentationJarRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class BuildInstruction
{
    /**
     * @var array
     */
    private static $typeMap = [
        'typo3-cms-documentation' => ['m' => 'manual'],
        'typo3-cms-framework' => ['c' => 'core-extension'],
        'typo3-cms-extension' => ['p' => 'extension'],
        '__default' => ['p', 'package']
    ];

    /**
     * @var Kernel
     */
    private $kernel;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * @var int
     */
    private $buildTime;

    /**
     * @var DocumentationJarRepository
     */
    private $documentationJarRepository;

    public function __construct(Kernel $kernel, EntityManagerInterface $entityManager, Filesystem $fileSystem)
    {
        $this->kernel = $kernel;
        $this->entityManager = $entityManager;
        $this->fileSystem = $fileSystem;
        $this->buildTime = ceil(microtime(true) * 10000);
    }

    /**
     * @param GithubPushEventForDocs $pushEventForDocs
     * @return string
     * @throws \Exception
     */
    public function generate(GithubPushEventForDocs $pushEventForDocs): string
    {
        $deploymentInformation = $this->generateDeploymentInformation($pushEventForDocs->composerFile, $pushEventForDocs->versionNumber);
        $this->assertBuildWasTriggeredByRepositoryOwner($deploymentInformation, $pushEventForDocs->repositoryUrl);

        $privateFilePath = implode('/', [
            $this->kernel->getCacheDir(),
            $deploymentInformation->getVendor(),
            $deploymentInformation->getName(),
            $deploymentInformation->getVersion(),
            'builds',
            $this->buildTime,
        ]);
        $publicFilePath = $this->kernel->getProjectDir() . '/builds/' . $this->buildTime;

        $this->entityManager->getConnection()->beginTransaction();
        try {
            $documentationJar = (new DocumentationJar())
                ->setRepositoryUrl($pushEventForDocs->repositoryUrl)
                ->setPackageName($deploymentInformation->getPackageName())
                ->setBranch($deploymentInformation->getVersion());
            $this->entityManager->persist($documentationJar);
            $this->entityManager->flush();

            if (!$this->fileSystem->exists($privateFilePath)) {
                // TODO: Move the string concatenation magic in an Encoder class?
                $this->fileSystem->appendToFile($privateFilePath, '#!/bin/bash' . PHP_EOL);
                // Write deployment information as file content for `source`ing in shell
                foreach ($deploymentInformation->toArray() as $property => $value) {
                    $envAssignmentString = $property . '=' . $value;
                    $this->fileSystem->appendToFile($privateFilePath, $envAssignmentString . PHP_EOL);
                }

                if (!$this->fileSystem->exists(dirname($publicFilePath))) {
                    $this->fileSystem->mkdir(dirname($publicFilePath));
                }
                $this->fileSystem->symlink($privateFilePath, $publicFilePath);
            }
            $this->entityManager->commit();
        } catch (\Exception $e) {
            if ($this->fileSystem->exists($privateFilePath)) {
                $this->fileSystem->remove($privateFilePath);
            }

            if ($this->fileSystem->exists($publicFilePath)) {
                $this->fileSystem->remove($publicFilePath);
            }
            $this->entityManager->getConnection()->rollBack();

            throw $e;
        }

        return $publicFilePath;
    }

    /**
     * @param string $composerFilePath
     * @param string $version
     * @return DeploymentInformation
     */
    private function generateDeploymentInformation(string $composerFilePath, string $version): DeploymentInformation
    {
        $composerJson = json_decode($this->fetchRemoteFile($composerFilePath), true);
        $packageName = $this->determinePackageName($composerJson);
        $packageType = $this->determinePackageType($composerJson);

        $deploymentInformation = (new DeploymentInformation())
            ->setVendor(key($packageName))
            ->setName(current($packageName))
            ->setVersion($version)
            ->setTypeLong(key($packageType))
            ->setTypeShort(current($packageType));

        return $deploymentInformation;
    }

    /**
     * @param DeploymentInformation $deploymentInformation
     * @param string $repositoryUrl
     */
    private function assertBuildWasTriggeredByRepositoryOwner(DeploymentInformation $deploymentInformation, string $repositoryUrl): void
    {
        $record = $this->documentationJarRepository->findOneBy([
            'package_name' => $deploymentInformation->getPackageName()
        ]);

        if ($record instanceof DocumentationJar && $record->getRepositoryUrl() !== $repositoryUrl) {
            throw new \RuntimeException(
                'Build was triggered by ' . $repositoryUrl . ' which seems to be a fork of ' . $record->getRepositoryUrl(),
                1553090750
            );
        }
    }

    /**
     * @param string $path
     * @return string
     */
    private function fetchRemoteFile(string $path): string
    {
        $response = (new GeneralClient())->request('GET', $path);
        $statusCode = $response->getStatusCode();

        if ($statusCode !== 200) {
            throw new IOException('Could not read remote file ' . $path . ', received status code ' . $statusCode, 1553081065);
        }

        return $response->getBody()->getContents();
    }

    /**
     * Check whether given version matches expected format and remove patch level from version
     *
     * @param string $version
     * @return string
     */
    private function normalizeVersionString(string $version): string
    {
        if ($version === 'latest') {
            // TODO: For the time being, the version "latest" is mapped to "master"
            $version = 'master';
        }

        if (!preg_match('/^master|(?:v?\d+.\d+.\d+)/$', $version)) {
            throw new \InvalidArgumentException('Invalid version format given, expected either "latest" "master" or \d.\d.\d.');
        }

        $version = ltrim($version, 'v');
        return implode('.', array_slice(explode('.', $version), 0, 2));
    }

    /**
     * @param array $composerJson
     * @return array
     * @throws \InvalidArgumentException
     */
    private function determinePackageType(array $composerJson): array
    {
        if (empty($composerJson['type'])) {
            throw new \InvalidArgumentException('No package type defined in composer.json', 1553081747);
        }

        if (isset(self::$typeMap[$composerJson['type']])) {
            return self::$typeMap[$composerJson['type']];
        }

        $this->logger->info('Received unmapped package type ' . $composerJson['type'] . ' as defined in ' . $this->composerFile . ', falling back to default');
        return self::$typeMap['__default'];
    }

    /**
     * @param array $composerJson
     * @return array
     */
    private function determinePackageName(array $composerJson): array
    {
        if (empty($composerJson['name'])) {
            throw new \InvalidArgumentException('No package name defined in composer.json', 1553082362);
        }

        if (!preg_match('/^[\w-]+\/[\w-]+$/', $composerJson['name'])) {
            throw new \InvalidArgumentException('Invalid package name ' . $composerJson['name'] . ' provided', 1553082490);
        }

        [$vendor, $name] = explode('/', $composerJson['name']);
        return [$vendor => $name];
    }
}
