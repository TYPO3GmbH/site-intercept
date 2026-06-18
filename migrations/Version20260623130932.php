<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260623130932 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE documentation_quarantine (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, domain VARCHAR(255) NOT NULL, serialized_push_event CLOB NOT NULL, checksum CHAR(32) NOT NULL)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX checksum_idx ON documentation_quarantine (checksum)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE known_repository_domain (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, domain VARCHAR(255) NOT NULL, status INTEGER NOT NULL, locked BOOLEAN DEFAULT 0 NOT NULL, last_hit DATETIME)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX domain_idx ON known_repository_domain (domain)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            DROP TABLE documentation_quarantine
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE known_repository_domain
        SQL);
    }
}
