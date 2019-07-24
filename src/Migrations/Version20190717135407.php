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
 * @codeCoverageIgnore
 */
final class Version20190717135407 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('CREATE TABLE discord_webhook (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, channel_id VARCHAR(255) DEFAULT NULL, name VARCHAR(255) NOT NULL, identifier VARCHAR(255) DEFAULT NULL, type INTEGER DEFAULT 0 NOT NULL, username VARCHAR(255) DEFAULT \'Intercept\' NOT NULL, avatar_url VARCHAR(255) DEFAULT NULL, log_level INTEGER DEFAULT NULL)');
        $this->addSql('CREATE INDEX IDX_CEE9330D72F5A1AA ON discord_webhook (channel_id)');
        $this->addSql('CREATE TABLE discord_channel (channel_id VARCHAR(255) NOT NULL, parent_id VARCHAR(255) DEFAULT NULL, channel_name VARCHAR(255) NOT NULL, channel_type INTEGER DEFAULT 0 NOT NULL, webhook_url VARCHAR(255) DEFAULT NULL, PRIMARY KEY(channel_id))');
        $this->addSql('CREATE INDEX IDX_E664AA1C727ACA70 ON discord_channel (parent_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('DROP TABLE discord_webhook');
        $this->addSql('DROP TABLE discord_channel');
    }
}
