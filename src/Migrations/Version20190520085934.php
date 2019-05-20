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
final class Version20190520085934 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('ALTER TABLE redirect ADD COLUMN is_legacy INTEGER DEFAULT \'0\' NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('CREATE TEMPORARY TABLE __temp__redirect AS SELECT id, created_at, updated_at, source, target, status_code FROM redirect');
        $this->addSql('DROP TABLE redirect');
        $this->addSql('CREATE TABLE redirect (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, created_at DATETIME NOT NULL --(DC2Type:datetime)
        , updated_at DATETIME NOT NULL --(DC2Type:datetime)
        , source VARCHAR(2000) NOT NULL, target VARCHAR(2000) NOT NULL, status_code INTEGER NOT NULL)');
        $this->addSql('INSERT INTO redirect (id, created_at, updated_at, source, target, status_code) SELECT id, created_at, updated_at, source, target, status_code FROM __temp__redirect');
        $this->addSql('DROP TABLE __temp__redirect');
    }
}
