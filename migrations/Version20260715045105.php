<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260715045105 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__documentation_jar AS SELECT id, repository_url, public_composer_json_url, vendor, name, package_name, package_type, extension_key, branch, created_at, last_rendered_at, target_branch_directory, type_long, type_short, minimum_typo_version, maximum_typo_version, status, build_key, re_render_needed, new, approved, last_rendered_link FROM documentation_jar
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE documentation_jar
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE documentation_jar (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, repository_url VARCHAR(255) NOT NULL, public_composer_json_url VARCHAR(255) DEFAULT '', vendor VARCHAR(255) DEFAULT '' NOT NULL, name VARCHAR(255) DEFAULT '' NOT NULL, package_name VARCHAR(255) NOT NULL, package_type VARCHAR(255) NOT NULL, extension_key VARCHAR(255) DEFAULT '', branch VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, last_rendered_at DATETIME DEFAULT CURRENT_TIMESTAMP, target_branch_directory VARCHAR(255) NOT NULL, type_long VARCHAR(255) DEFAULT '' NOT NULL, type_short VARCHAR(255) DEFAULT '' NOT NULL, minimum_typo_version VARCHAR(20) DEFAULT '' NOT NULL, maximum_typo_version VARCHAR(20) DEFAULT '' NOT NULL, status INTEGER DEFAULT 0 NOT NULL, build_key VARCHAR(255) DEFAULT '' NOT NULL, re_render_needed BOOLEAN DEFAULT 0 NOT NULL, new BOOLEAN DEFAULT 0 NOT NULL, approved BOOLEAN DEFAULT 1 NOT NULL, last_rendered_link VARCHAR(255) DEFAULT '' NOT NULL)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO documentation_jar (id, repository_url, public_composer_json_url, vendor, name, package_name, package_type, extension_key, branch, created_at, last_rendered_at, target_branch_directory, type_long, type_short, minimum_typo_version, maximum_typo_version, status, build_key, re_render_needed, new, approved, last_rendered_link) SELECT id, repository_url, public_composer_json_url, vendor, name, package_name, package_type, extension_key, branch, created_at, last_rendered_at, target_branch_directory, type_long, type_short, minimum_typo_version, maximum_typo_version, status, build_key, re_render_needed, new, approved, last_rendered_link FROM __temp__documentation_jar
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__documentation_jar
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX uniq_documentation_jar ON documentation_jar (repository_url, package_name, target_branch_directory)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__documentation_jar AS SELECT id, repository_url, public_composer_json_url, vendor, name, package_name, package_type, extension_key, branch, created_at, last_rendered_at, target_branch_directory, type_long, type_short, minimum_typo_version, maximum_typo_version, status, build_key, re_render_needed, new, approved, last_rendered_link FROM documentation_jar
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE documentation_jar
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE documentation_jar (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, repository_url VARCHAR(255) NOT NULL, public_composer_json_url VARCHAR(255) DEFAULT '', vendor VARCHAR(255) DEFAULT '' NOT NULL, name VARCHAR(255) DEFAULT '' NOT NULL, package_name VARCHAR(255) NOT NULL, package_type VARCHAR(255) NOT NULL, extension_key VARCHAR(255) DEFAULT '', branch VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, last_rendered_at DATETIME DEFAULT CURRENT_TIMESTAMP, target_branch_directory VARCHAR(255) NOT NULL, type_long VARCHAR(255) DEFAULT '' NOT NULL, type_short VARCHAR(255) DEFAULT '' NOT NULL, minimum_typo_version VARCHAR(20) DEFAULT '' NOT NULL, maximum_typo_version VARCHAR(20) DEFAULT '' NOT NULL, status INTEGER DEFAULT 0 NOT NULL, build_key VARCHAR(255) DEFAULT '' NOT NULL, re_render_needed BOOLEAN DEFAULT 0 NOT NULL, new BOOLEAN DEFAULT 0 NOT NULL, approved BOOLEAN DEFAULT 1 NOT NULL, last_rendered_link VARCHAR(255) DEFAULT '' NOT NULL)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO documentation_jar (id, repository_url, public_composer_json_url, vendor, name, package_name, package_type, extension_key, branch, created_at, last_rendered_at, target_branch_directory, type_long, type_short, minimum_typo_version, maximum_typo_version, status, build_key, re_render_needed, new, approved, last_rendered_link) SELECT id, repository_url, public_composer_json_url, vendor, name, package_name, package_type, extension_key, branch, created_at, last_rendered_at, target_branch_directory, type_long, type_short, minimum_typo_version, maximum_typo_version, status, build_key, re_render_needed, new, approved, last_rendered_link FROM __temp__documentation_jar
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__documentation_jar
        SQL);
    }
}
