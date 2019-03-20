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
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Command to create a shell script that set environment variables required for further documentation
 * rendering and deployment.
 *
 * @codeCoverageIgnore
 */
class CreateDocumentationInformationScriptCommand extends Command
{
    private static $typeMap = [
        'typo3-cms-documentation' => ['m' => 'manual'],
        'typo3-cms-framework' => ['c' => 'core-extension'],
        'typo3-cms-extension' => ['p' => 'extension'],
        '__default' => ['p', 'package']
    ];

    /**
     * @var string
     */
    private $composerFile = '';

    /**
     * @var string
     */
    private $targetFile = '';

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
     * Constructor
     *
     * @param string|null $name
     * @param Filesystem $fileSystem
     * @param GeneralClient $client
     * @param LoggerInterface $logger
     */
    public function __construct(?string $name, Filesystem $fileSystem, GeneralClient $client, LoggerInterface $logger)
    {
        parent::__construct($name);

        $this->fileSystem = $fileSystem;
        $this->client = $client;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this
            ->setName('prepare:deployment')
            ->setDescription('Prepares deployment, e.g. define some variables.')
            ->addArgument('targetFile', InputArgument::REQUIRED, 'Path to file containing the output')
            ->addArgument('repositoryUrl', InputArgument::REQUIRED, 'URL to the remote repository')
            ->addArgument('composerFile', InputArgument::REQUIRED, 'URL of the remote composer.json')
            ->addArgument('version', InputArgument::REQUIRED, 'The rendered version, e.g. "master" or "10.5.2"');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->composerFile = $input->getArgument('composerFile');
        $this->targetFile = $input->getArgument('targetFile');
        $this->assertFileOrFolderIsAccessible(dirname($this->targetFile));
        $version = $this->normalizeVersionString($input->getArgument('version'));

        try {
            // TODO: Store information somewhere in sqlite

            $deploymentInformation = $this->generateDeploymentInformation($this->composerFile, $version);

            // TODO: Store array in file
        } catch (\Exception $e)  {
            // TODO: Rollback
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return 2;
        }

        return 0;
    }

    /**
     * @param string $composerFilePath
     * @param string $version
     * @return array
     */
    private function generateDeploymentInformation(string $composerFilePath, string $version): array
    {
        $composerJson = json_decode($this->fetchRemoteFile($composerFilePath), true);
        $packageType = $this->determinePackageType($composerJson);
        $packageName = $this->determinePackageName($composerJson);
        $deploymentInformation = [
            'type_long' => key($packageType),
            'type_short' => current($packageType),
            'vendor' => key($packageName),
            'name' => current($packageName),
            'version' => $version,
        ];

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
     * @param string $path
     * @throws IOException
     */
    private function assertFileOrFolderIsAccessible(string $path): void
    {
        if (!is_readable($path)) {
            throw new IOException('Directory or file ' . $path . ' is not readable', 1553077522);
        }

        if (!file_exists($path)) {
            throw new IOException('Directory or file ' . $path . ' does not exist', 1553077528);
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