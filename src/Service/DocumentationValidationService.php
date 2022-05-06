<?php
declare(strict_types = 1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace App\Service;

use App\Client\GeneralClient;
use App\Exception\DocsNotValidException;
use App\Exception\FileNotFoundException;
use App\Extractor\ComposerJson;
use App\Extractor\PushEvent;
use GuzzleHttp\Exception\GuzzleException;

/**
 * This service class validates the documentation based on the TYPO3 documentation standards and raises an exception
 * containing all validation errors.
 */
class DocumentationValidationService
{
    private GeneralClient $client;

    /**
     * Constructor
     */
    public function __construct(
        GeneralClient $client
    ) {
        $this->client = $client;
    }

    /**
     * Validate the documentation based on the TYPO3 documentation standards and raise an exception containing all
     * validation errors.
     *
     * @param PushEvent $pushEvent
     * @param ComposerJson $composerJson
     * @return void
     * @throws DocsNotValidException
     */
    public function validate(PushEvent $pushEvent, ComposerJson $composerJson): void
    {
        $files = $this->fetchFiles($pushEvent);
        $errors = $this->validateFiles($files, $composerJson);

        if (count($errors)) {
            // Handle documentation validation errors gently for a grace period. Adapt this message when the grace
            // period has expired.
            $message = 'The documentation format is outdated:' . "\n\n";
            $message.= '- ' . implode("\n- ", $errors) . "\n\n";
            $message.= 'Use the current grace period to fix the documentation warnings and adapt them to the TYPO3 documentation standards [1][2].' . "\n\n";
            $message.= '[1] https://docs.typo3.org/m/typo3/docs-how-to-document/main/en-us/GeneralConventions/FileStructure.html' . "\n";
            $message.= '[2] https://docs.typo3.org/m/typo3/docs-how-to-document/main/en-us/UserRoundTrip.html';
            throw new DocsNotValidException($message, 1651671307);
        }
    }

    private function fetchFiles(PushEvent $pushEvent): array
    {
        $files = [];

        $paths = [
            'README.rst',
            'README.md',
            'Readme.rst',
            'Readme.md',
            'readme.rst',
            'readme.md',
        ];
        foreach ($paths as $path) {
            try {
                $files['README.rst'] = $this->fetchRemoteDocumentationFile($pushEvent->getUrlToFile($path));
                break;
            } catch (FileNotFoundException $e) {
            }
        }
        $paths = [
            'Documentation/genindex.rst',
            'Documentation/Includes.rst.txt',
            'Documentation/Index.rst',
            'Documentation/Settings.cfg',
            'Documentation/Sitemap.rst',
        ];
        foreach ($paths as $path) {
            try {
                $files[$path] = $this->fetchRemoteDocumentationFile($pushEvent->getUrlToFile($path));
            } catch (FileNotFoundException $e) {
            }
        }
        $paths = [
            'Documentation/_make/Makefile',
            'Documentation/Sitemap/Index.rst',
            'Documentation/Includes.txt',
            'Documentation/Settings.yml',
            'Documentation/Targets.rst',
        ];
        foreach ($paths as $path) {
            try {
                $this->fetchRemoteDocumentationFile($pushEvent->getUrlToFile($path));
                $files[$path] = '';
            } catch (FileNotFoundException $e) {
            }
        }

        return $files;
    }

    private function fetchRemoteDocumentationFile(string $path): string
    {
        try {
            $response = $this->client->request('GET', $path);
        } catch (GuzzleException $e) {
            throw new FileNotFoundException($e->getMessage());
        }
        $statusCode = $response->getStatusCode();
        if ($statusCode !== 200) {
            throw new FileNotFoundException('Fetching ' . $path . ' did not return HTTP 200');
        }
        return (string)$response->getBody();
    }

    private function validateFiles(array $files, ComposerJson $composerJson): array
    {
        $errors = [];

        if (!isset($files['README.rst'])) {
            $errors[] = 'README file is missing in the project root.';
        } else {
            if (!str_contains($files['README.rst'], 'docs.typo3.org')) {
                $errors[] = 'README file misses link to rendered documentation on docs.typo3.org.';
            }
            if ($composerJson->getType() === 'typo3-cms-extension') {
                if (!str_contains($files['README.rst'], 'poser.pugx.org')) {
                    $errors[] = 'README file misses badges with store statistics.';
                }
                if (!str_contains($files['README.rst'], 'img.shields.io/badge/TYPO3')) {
                    $errors[] = 'README file misses badges with TYPO3 compatibility.';
                }
                if (!str_contains($files['README.rst'], 'extensions.typo3.org')) {
                    $errors[] = 'README file misses link to project page on extensions.typo3.org (TER).';
                }
            }
        }

        if (!isset($files['Documentation/Settings.cfg'])) {
            if (in_array($composerJson->getType(), ['typo3-cms-documentation', 'typo3-cms-framework', 'typo3-cms-extension'])) {
                $errors[] = 'Settings.cfg is missing in Documentation/.';
            }
        } else {
            $settingsCfg = $this->parseIniString($files['Documentation/Settings.cfg']);
            if (empty($settingsCfg)) {
                $errors[] = 'Documentation/Settings.cfg has syntax errors.';
            } else {
                $outdated = ['t3author', 'description', 'github_commit_hash', 'github_revision_msg', 'github_sphinx_locale', 't3core'];
                $outdated = array_filter($outdated, function ($property) use ($settingsCfg) {
                    return isset($settingsCfg[$property]);
                });
                if (count($outdated)) {
                    $errors[] = 'Documentation/Settings.cfg contains outdated properties: ' . implode(', ', $outdated) . '.';
                }
                $missing = ['project', 'version', 'release', 'copyright', 'project_home', 'project_contact', 'project_repository', 'project_issues'];
                $missing = array_filter($missing, function ($property) use ($settingsCfg) {
                    return empty($settingsCfg[$property]);
                });
                if (count($missing)) {
                    $errors[] = 'Documentation/Settings.cfg misses proper values for properties: ' . implode(', ', $missing) . '.';
                }
            }
        }

        if (isset($files['Documentation/Index.rst'])) {
            if (!str_contains($files['Documentation/Index.rst'], 'Includes.rst.txt')) {
                $errors[] = 'Documentation/Index.rst misses including the /Includes.rst.txt.';
            }
            preg_match_all('/^\s*:([A-Za-z0-9 ]+):\s/m', $files['Documentation/Index.rst'], $matches);
            $fields = array_flip($matches[1] ?? []);
            $outdated = ['Description', 'Keywords', 'Copyright', 'Classification'];
            $outdated = array_filter($outdated, function ($name) use ($fields) {
                return isset($fields[$name]);
            });
            if (count($outdated)) {
                $errors[] = 'Documentation/Index.rst contains outdated fields: ' . implode(', ', $outdated) . '.';
            }
            if ($composerJson->getType() === 'typo3-cms-documentation') {
                $missing = ['Version', 'Language', 'Author', 'License', 'Rendered'];
            } elseif (in_array($composerJson->getType(), ['typo3-cms-framework', 'typo3-cms-extension'])) {
                $missing = ['Extension key', 'Package name', 'Version', 'Language', 'Author', 'License', 'Rendered'];
            } else {
                $missing = ['Package name', 'Version', 'Language', 'Author', 'License', 'Rendered'];
            }
            $missing = array_filter($missing, function ($name) use ($fields) {
                return !isset($fields[$name]);
            });
            if (count($missing)) {
                $errors[] = 'Documentation/Index.rst misses the fields: ' . implode(', ', $missing) . '.';
            }
            if (!str_contains($files['Documentation/Index.rst'], '.. toctree::')) {
                $errors[] = 'Documentation/Index.rst misses the table of contents.';
            }

            if (!isset($files['Documentation/Includes.rst.txt'])) {
                $errors[] = 'Includes.rst.txt is missing in Documentation/.';
            }
            if (!isset($files['Documentation/Sitemap.rst'])) {
                $errors[] = 'Sitemap.rst is missing in Documentation/.';
            }
            if (!isset($files['Documentation/genindex.rst'])) {
                $errors[] = 'genindex.rst is missing in Documentation/.';
            }
        }

        $outdated = [
            'Documentation/_make/Makefile',
            'Documentation/Sitemap/Index.rst',
            'Documentation/Includes.txt',
            'Documentation/Settings.yml',
            'Documentation/Targets.rst',
        ];
        $outdated = array_filter($outdated, function ($file) use ($files) {
            return isset($files[$file]);
        });
        if (count($outdated)) {
            $errors[] = 'These files are outdated and should be removed: ' . implode(', ', $outdated) . '.';
        }

        return $errors;
    }

    /**
     * PHP internal parsing of INI file content does not support some characters in keys and values that are commonly
     * used in Settings.cfg. These reserved characters are escaped before parsing and unescaped after parsing.
     *
     * @param string $content
     * @return array
     */
    private function parseIniString(string $content): array
    {
        $parsed = [];

        try {
            $search = ['?', '{', '}', '|', '&', '~', '!', '\\', '[', ']', '(', ')', '^'];
            $replace = array_map(function ($character) {
                return sprintf('---%s---', ord($character));
            }, $search);
            $content = preg_replace_callback('/^.*$/m', function ($matches) use ($search, $replace) {
                return str_starts_with($matches[0], '#') ? '' : # remove comments
                    (str_starts_with($matches[0], ';') ? '' : # remove comments
                        (str_starts_with($matches[0], '[') ? $matches[0] : # leave sections unescaped
                            str_replace($search, $replace, $matches[0]))); # escape key-value pairs
            }, $content);
            $parsed = parse_ini_string($content, false, INI_SCANNER_RAW);
            $parsed = array_map(function ($line) use ($search, $replace) {
                return str_replace($replace, $search, $line);
            }, $parsed);
        } catch (\ErrorException $e) {
            // noop
        }

        return $parsed;
    }
}
