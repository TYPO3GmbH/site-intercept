<?php

declare(strict_types=1);

/*
 * This file is part of the package t3g/intercept.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210923113505 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE discord_channel (channel_id VARCHAR(255) NOT NULL, parent_id VARCHAR(255) DEFAULT NULL, channel_name VARCHAR(255) NOT NULL, channel_type INTEGER DEFAULT 0 NOT NULL, webhook_url VARCHAR(255) DEFAULT NULL, PRIMARY KEY(channel_id))');
        $this->addSql('CREATE INDEX IDX_E664AA1C727ACA70 ON discord_channel (parent_id)');
        $this->addSql('CREATE TABLE discord_scheduled_message (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, channel_id VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, message VARCHAR(2000) NOT NULL, schedule VARCHAR(255) NOT NULL, timezone VARCHAR(75) NOT NULL, username VARCHAR(255) DEFAULT NULL, avatar_url VARCHAR(255) DEFAULT NULL)');
        $this->addSql('CREATE INDEX IDX_4852386172F5A1AA ON discord_scheduled_message (channel_id)');
        $this->addSql('CREATE TABLE discord_webhook (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, channel_id VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, identifier VARCHAR(255) DEFAULT NULL, type INTEGER DEFAULT 0 NOT NULL, username VARCHAR(255) DEFAULT \'Intercept\' NOT NULL, avatar_url VARCHAR(255) DEFAULT NULL, log_level INTEGER DEFAULT NULL)');
        $this->addSql('CREATE INDEX IDX_CEE9330D72F5A1AA ON discord_webhook (channel_id)');
        $this->addSql('CREATE TABLE documentation_jar (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, repository_url VARCHAR(255) NOT NULL, public_composer_json_url VARCHAR(255) DEFAULT \'\' NOT NULL, vendor VARCHAR(255) DEFAULT \'\' NOT NULL, name VARCHAR(255) DEFAULT \'\' NOT NULL, package_name VARCHAR(255) NOT NULL, package_type VARCHAR(255) NOT NULL, extension_key VARCHAR(255) DEFAULT \'\', branch VARCHAR(255) NOT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, last_rendered_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, target_branch_directory VARCHAR(255) NOT NULL, type_long VARCHAR(255) DEFAULT \'\' NOT NULL, type_short VARCHAR(255) DEFAULT \'\' NOT NULL, minimum_typo_version VARCHAR(20) DEFAULT \'\' NOT NULL, maximum_typo_version VARCHAR(20) DEFAULT \'\' NOT NULL, status INTEGER DEFAULT 0 NOT NULL, build_key VARCHAR(255) DEFAULT \'\' NOT NULL, re_render_needed BOOLEAN DEFAULT \'0\' NOT NULL, new BOOLEAN DEFAULT \'0\' NOT NULL, approved BOOLEAN DEFAULT \'1\' NOT NULL, last_rendered_link VARCHAR(255) DEFAULT \'\' NOT NULL)');
        $this->addSql('CREATE TABLE history_entry (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, type VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, group_entry VARCHAR(255) DEFAULT \'default\' NOT NULL, data CLOB NOT NULL --(DC2Type:json)
        , created_at DATETIME NOT NULL --(DC2Type:datetime_immutable)
        )');
        $this->addSql('CREATE TABLE redirect (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, source VARCHAR(2000) NOT NULL, target VARCHAR(2000) NOT NULL, is_legacy INTEGER NOT NULL, status_code INTEGER NOT NULL)');
        $this->addSql('CREATE TABLE repository_blacklist_entry (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, repository_url VARCHAR(255) NOT NULL)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE discord_channel');
        $this->addSql('DROP TABLE discord_scheduled_message');
        $this->addSql('DROP TABLE discord_webhook');
        $this->addSql('DROP TABLE documentation_jar');
        $this->addSql('DROP TABLE history_entry');
        $this->addSql('DROP TABLE redirect');
        $this->addSql('DROP TABLE repository_blacklist_entry');
    }
}
