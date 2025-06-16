<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250616104021 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP TABLE bamboo_nightly_build
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE discord_channel
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE discord_scheduled_message
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE discord_webhook
        SQL);
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
            CREATE TEMPORARY TABLE __temp__history_entry AS SELECT id, type, status, group_entry, data, created_at FROM history_entry
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE history_entry
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE history_entry (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, type VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, group_entry VARCHAR(255) DEFAULT 'default' NOT NULL, data CLOB NOT NULL --(DC2Type:json)
            , created_at DATETIME DEFAULT NULL --(DC2Type:datetime_immutable)
            )
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO history_entry (id, type, status, group_entry, data, created_at) SELECT id, type, status, group_entry, data, created_at FROM __temp__history_entry
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__history_entry
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__redirect AS SELECT id, created_at, updated_at, source, target, is_legacy, status_code FROM redirect
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE redirect
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE redirect (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, source VARCHAR(2000) NOT NULL, target VARCHAR(2000) NOT NULL, is_legacy BOOLEAN NOT NULL, status_code INTEGER NOT NULL)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO redirect (id, created_at, updated_at, source, target, is_legacy, status_code) SELECT id, created_at, updated_at, source, target, is_legacy, status_code FROM __temp__redirect
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__redirect
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE bamboo_nightly_build (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, build_key VARCHAR(255) NOT NULL COLLATE "BINARY", failed_runs INTEGER UNSIGNED DEFAULT 0 NOT NULL)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE discord_channel (channel_id VARCHAR(255) NOT NULL COLLATE "BINARY", parent_id VARCHAR(255) DEFAULT NULL COLLATE "BINARY", channel_name VARCHAR(255) NOT NULL COLLATE "BINARY", channel_type INTEGER DEFAULT 0 NOT NULL, webhook_url VARCHAR(255) DEFAULT NULL COLLATE "BINARY", PRIMARY KEY(channel_id), CONSTRAINT FK_E664AA1C727ACA70 FOREIGN KEY (parent_id) REFERENCES discord_channel (channel_id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_E664AA1C727ACA70 ON discord_channel (parent_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE discord_scheduled_message (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, channel_id VARCHAR(255) DEFAULT NULL COLLATE "BINARY", name VARCHAR(255) NOT NULL COLLATE "BINARY", message VARCHAR(2000) NOT NULL COLLATE "BINARY", timezone VARCHAR(75) NOT NULL COLLATE "BINARY", username VARCHAR(255) DEFAULT NULL COLLATE "BINARY", avatar_url VARCHAR(255) DEFAULT NULL COLLATE "BINARY", schedule VARCHAR(255) NOT NULL COLLATE "BINARY", CONSTRAINT FK_4852386172F5A1AA FOREIGN KEY (channel_id) REFERENCES discord_channel (channel_id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_4852386172F5A1AA ON discord_scheduled_message (channel_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE discord_webhook (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, channel_id VARCHAR(255) DEFAULT NULL COLLATE "BINARY", name VARCHAR(255) NOT NULL COLLATE "BINARY", identifier VARCHAR(255) DEFAULT NULL COLLATE "BINARY", type INTEGER DEFAULT 0 NOT NULL, username VARCHAR(255) DEFAULT 'Intercept' NOT NULL COLLATE "BINARY", avatar_url VARCHAR(255) DEFAULT NULL COLLATE "BINARY", log_level INTEGER DEFAULT NULL, CONSTRAINT FK_CEE9330D72F5A1AA FOREIGN KEY (channel_id) REFERENCES discord_channel (channel_id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_CEE9330D72F5A1AA ON discord_webhook (channel_id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__documentation_jar AS SELECT id, repository_url, public_composer_json_url, vendor, name, package_name, package_type, extension_key, branch, created_at, last_rendered_at, target_branch_directory, type_long, type_short, minimum_typo_version, maximum_typo_version, status, build_key, re_render_needed, new, approved, last_rendered_link FROM documentation_jar
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE documentation_jar
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE documentation_jar (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, repository_url VARCHAR(255) NOT NULL, public_composer_json_url VARCHAR(255) DEFAULT '' NOT NULL, vendor VARCHAR(255) DEFAULT '' NOT NULL, name VARCHAR(255) DEFAULT '' NOT NULL, package_name VARCHAR(255) NOT NULL, package_type VARCHAR(255) NOT NULL, extension_key VARCHAR(255) DEFAULT '', branch VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, last_rendered_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, target_branch_directory VARCHAR(255) NOT NULL, type_long VARCHAR(255) DEFAULT '' NOT NULL, type_short VARCHAR(255) DEFAULT '' NOT NULL, minimum_typo_version VARCHAR(20) DEFAULT '' NOT NULL, maximum_typo_version VARCHAR(20) DEFAULT '' NOT NULL, status INTEGER DEFAULT 0 NOT NULL, build_key VARCHAR(255) DEFAULT '' NOT NULL, re_render_needed BOOLEAN DEFAULT 0 NOT NULL, new BOOLEAN DEFAULT 0 NOT NULL, approved BOOLEAN DEFAULT 1 NOT NULL, last_rendered_link VARCHAR(255) DEFAULT '' NOT NULL)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO documentation_jar (id, repository_url, public_composer_json_url, vendor, name, package_name, package_type, extension_key, branch, created_at, last_rendered_at, target_branch_directory, type_long, type_short, minimum_typo_version, maximum_typo_version, status, build_key, re_render_needed, new, approved, last_rendered_link) SELECT id, repository_url, public_composer_json_url, vendor, name, package_name, package_type, extension_key, branch, created_at, last_rendered_at, target_branch_directory, type_long, type_short, minimum_typo_version, maximum_typo_version, status, build_key, re_render_needed, new, approved, last_rendered_link FROM __temp__documentation_jar
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__documentation_jar
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__history_entry AS SELECT id, type, status, group_entry, data, created_at FROM history_entry
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE history_entry
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE history_entry (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, type VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, group_entry VARCHAR(255) DEFAULT 'default' NOT NULL, data CLOB NOT NULL --(DC2Type:json)
            , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
            )
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO history_entry (id, type, status, group_entry, data, created_at) SELECT id, type, status, group_entry, data, created_at FROM __temp__history_entry
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__history_entry
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__redirect AS SELECT id, created_at, updated_at, source, target, is_legacy, status_code FROM redirect
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE redirect
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE redirect (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, source VARCHAR(2000) NOT NULL, target VARCHAR(2000) NOT NULL, is_legacy INTEGER NOT NULL, status_code INTEGER NOT NULL)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO redirect (id, created_at, updated_at, source, target, is_legacy, status_code) SELECT id, created_at, updated_at, source, target, is_legacy, status_code FROM __temp__redirect
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__redirect
        SQL);
    }
}
