<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace DoctrineMigrations;

use App\Extractor\ComposerJson;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 * @codeCoverageIgnore
 */
final class Version20190521074938 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('CREATE TEMPORARY TABLE __temp__documentation_jar AS SELECT id, repository_url, package_name, branch, created_at, last_rendered_at, target_branch_directory, type_long, type_short, public_composer_json_url, vendor, name, status, build_key, package_type FROM documentation_jar');
        $this->addSql('DROP TABLE documentation_jar');
        $this->addSql('CREATE TABLE documentation_jar (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, repository_url VARCHAR(255) NOT NULL COLLATE BINARY, package_name VARCHAR(255) NOT NULL COLLATE BINARY, branch VARCHAR(255) NOT NULL COLLATE BINARY, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL --(DC2Type:datetime)
        , last_rendered_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL --(DC2Type:datetime)
        , target_branch_directory VARCHAR(255) NOT NULL COLLATE BINARY, type_long VARCHAR(255) DEFAULT \'\' NOT NULL COLLATE BINARY, type_short VARCHAR(255) DEFAULT \'\' NOT NULL COLLATE BINARY, public_composer_json_url VARCHAR(255) DEFAULT \'\' NOT NULL COLLATE BINARY, vendor VARCHAR(255) DEFAULT \'\' NOT NULL COLLATE BINARY, name VARCHAR(255) DEFAULT \'\' NOT NULL COLLATE BINARY, status INTEGER DEFAULT 0 NOT NULL, build_key VARCHAR(255) DEFAULT \'\' NOT NULL, package_type VARCHAR(255) NOT NULL, extension_key VARCHAR(255))');
        $this->addSql('INSERT INTO documentation_jar (id, repository_url, package_name, branch, created_at, last_rendered_at, target_branch_directory, type_long, type_short, public_composer_json_url, vendor, name, status, build_key, package_type) SELECT id, repository_url, package_name, branch, created_at, last_rendered_at, target_branch_directory, type_long, type_short, public_composer_json_url, vendor, name, status, build_key, package_type FROM __temp__documentation_jar');
        $this->addSql('DROP TABLE __temp__documentation_jar');

        $queryBuilder = $this->connection->createQueryBuilder();
        $result = $queryBuilder
            ->select('id', 'package_name', 'package_type')
            ->from('documentation_jar')
            ->where(
                $queryBuilder->expr()->orX(
                    $queryBuilder->expr()->eq('package_type', $queryBuilder->createNamedParameter('typo3-cms-extension')),
                    $queryBuilder->expr()->eq('package_type', $queryBuilder->createNamedParameter('typo3-cms-framework'))
                )
            )->execute();

        while ($row = $result->fetch()) {
            $extensionName = $this->generateExtensionKeyFromPackageName($row['package_name'], $row['package_type']);
            $this->addSql('UPDATE documentation_jar SET extension_key = \'' . $extensionName . '\' WHERE id = ' . (int)$row['id']);
        }
        $result->closeCursor();
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('CREATE TEMPORARY TABLE __temp__documentation_jar AS SELECT id, repository_url, public_composer_json_url, vendor, name, package_name, package_type, branch, created_at, last_rendered_at, target_branch_directory, type_long, type_short, status, build_key FROM documentation_jar');
        $this->addSql('DROP TABLE documentation_jar');
        $this->addSql('CREATE TABLE documentation_jar (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, repository_url VARCHAR(255) NOT NULL, public_composer_json_url VARCHAR(255) DEFAULT \'\' NOT NULL, vendor VARCHAR(255) DEFAULT \'\' NOT NULL, name VARCHAR(255) DEFAULT \'\' NOT NULL, package_name VARCHAR(255) NOT NULL, branch VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL --(DC2Type:datetime)
        , last_rendered_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL --(DC2Type:datetime)
        , target_branch_directory VARCHAR(255) NOT NULL, type_long VARCHAR(255) DEFAULT \'\' NOT NULL, type_short VARCHAR(255) DEFAULT \'\' NOT NULL, package_type VARCHAR(255) DEFAULT \'\' NOT NULL COLLATE BINARY, status VARCHAR(255) DEFAULT \'\' NOT NULL COLLATE BINARY, build_key INTEGER DEFAULT 0 NOT NULL)');
        $this->addSql('INSERT INTO documentation_jar (id, repository_url, public_composer_json_url, vendor, name, package_name, package_type, branch, created_at, last_rendered_at, target_branch_directory, type_long, type_short, status, build_key) SELECT id, repository_url, public_composer_json_url, vendor, name, package_name, package_type, branch, created_at, last_rendered_at, target_branch_directory, type_long, type_short, status, build_key FROM __temp__documentation_jar');
        $this->addSql('DROP TABLE __temp__documentation_jar');
    }

    /**
     * @param string $packageName
     * @param string $packageType
     * @return string
     */
    private function generateExtensionKeyFromPackageName(string $packageName, string $packageType): string
    {
        $stubComposerJson = new ComposerJson([
            'name' => $packageName,
            'type' => $packageType
        ]);

        $extensionKey = $stubComposerJson->getExtensionKey();

        // Since we run in a stub context, we miss information and need to strip the 'cms_' prefix generated e.g. from typo3/cms-form
        return str_replace('cms_', '', $extensionKey);
    }
}
