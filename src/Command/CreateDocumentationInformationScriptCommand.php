<?php
declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Command;

use App\Client\GeneralClient;
use App\Entity\DeploymentInformation;
use App\Entity\DocumentationJar;
use App\Repository\DocumentationJarRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Command to create a shell script that set environment variables required for further documentation
 * rendering and deployment.
 *
 * @codeCoverageIgnore
 */
class CreateDocumentationInformationScriptCommand extends Command
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
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * @var GeneralClient
     */
    private $client;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var string
     */
    private $composerFile = '';

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var DocumentationJarRepository
     */
    private $documentationJarRepository;

    /**
     * Constructor
     *
     * @param KernelInterface $kernel
     * @param Filesystem $fileSystem
     * @param GeneralClient $client
     * @param LoggerInterface $logger
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(KernelInterface $kernel, Filesystem $fileSystem, GeneralClient $client, LoggerInterface $logger, EntityManagerInterface $entityManager)
    {
        parent::__construct();

        $this->fileSystem = $fileSystem;
        $this->kernel = $kernel;
        $this->client = $client;
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }

    protected function configure(): void
    {
        $this
            ->setName('prepare:deployment')
            ->setDescription('Prepares deployment, e.g. define some variables.')
            ->addArgument('version', InputArgument::REQUIRED, 'The rendered version, e.g. "master" or "10.5.2"')
            ->addArgument('repositoryUrl', InputArgument::REQUIRED, 'URL to the remote repository')
            ->addArgument('composerFile', InputArgument::REQUIRED, 'URL of the remote composer.json')
            ->addArgument('targetFileName', InputArgument::REQUIRED, 'Microtime of build');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $returnCode = 0;

        $targetFileName = $input->getArgument('targetFileName');
        $repositoryUrl = $input->getArgument('repositoryUrl');
        $this->composerFile = $input->getArgument('composerFile');
        $version = $this->normalizeVersionString($input->getArgument('version'));
        $this->documentationJarRepository = $this->entityManager->getRepository(DocumentationJar::class);

        $deploymentInformation = $this->generateDeploymentInformation($this->composerFile, $version);
        $this->assertBuildWasTriggeredByRepositoryOwner($deploymentInformation, $input->getArgument('repositoryUrl'));

        $privateFilePath = implode('/', [
            $this->kernel->getCacheDir(),
            $deploymentInformation->getVendor(),
            $deploymentInformation->getName(),
            $deploymentInformation->getVersion(),
            basename($targetFileName),
        ]);
        $publicFilePath = $this->kernel->getProjectDir() . $targetFileName;

        $this->entityManager->getConnection()->beginTransaction();
        try {
            $documentationJar = (new DocumentationJar())
                ->setRepositoryUrl($repositoryUrl)
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
            $this->entityManager->getConnection()->rollBack();
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            $returnCode = 2;
        } finally {
            if ($this->fileSystem->exists($publicFilePath)) {
                $this->fileSystem->remove($publicFilePath);
            }
        }

        return $returnCode;
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
     * @param string $path
     * @return string
     */
    private function fetchRemoteFile(string $path): string
    {
        $response = $this->client->request('GET', $path);
        $statusCode = $response->getStatusCode();

        if ($statusCode !== 200) {
            throw new IOException('Could not read remote file ' . $path . ', received status code ' . $statusCode, 1553081065);
        }

        return $response->getBody()->getContents();
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
