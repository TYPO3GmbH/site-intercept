<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260625125846 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__history_entry AS SELECT id, type, status, group_entry, data, created_at FROM history_entry
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE history_entry
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE history_entry (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, type VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, group_entry VARCHAR(255) DEFAULT 'default' NOT NULL, data CLOB NOT NULL, created_at DATETIME DEFAULT NULL)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO history_entry (id, type, status, group_entry, data, created_at) SELECT id, type, status, group_entry, data, created_at FROM __temp__history_entry
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__history_entry
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TEMPORARY TABLE __temp__history_entry AS SELECT id, type, status, group_entry, data, created_at FROM history_entry
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE history_entry
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE history_entry (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, type VARCHAR(13) NOT NULL, status VARCHAR(255) NOT NULL, group_entry VARCHAR(255) DEFAULT 'default' NOT NULL, data CLOB NOT NULL, created_at DATETIME DEFAULT NULL)
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO history_entry (id, type, status, group_entry, data, created_at) SELECT id, type, status, group_entry, data, created_at FROM __temp__history_entry
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE __temp__history_entry
        SQL);
    }
}
