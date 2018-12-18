<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181218141636 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('CREATE TEMPORARY TABLE __temp__bamboo_nightly_build AS SELECT id, build_key, failed_runs FROM bamboo_nightly_build');
        $this->addSql('DROP TABLE bamboo_nightly_build');
        $this->addSql('CREATE TABLE bamboo_nightly_build (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, build_key VARCHAR(255) NOT NULL COLLATE BINARY, failed_runs INTEGER UNSIGNED DEFAULT 0 NOT NULL)');
        $this->addSql('INSERT INTO bamboo_nightly_build (id, build_key, failed_runs) SELECT id, build_key, failed_runs FROM __temp__bamboo_nightly_build');
        $this->addSql('DROP TABLE __temp__bamboo_nightly_build');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'sqlite', 'Migration can only be executed safely on \'sqlite\'.');

        $this->addSql('CREATE TEMPORARY TABLE __temp__bamboo_nightly_build AS SELECT id, build_key, failed_runs FROM bamboo_nightly_build');
        $this->addSql('DROP TABLE bamboo_nightly_build');
        $this->addSql('CREATE TABLE bamboo_nightly_build (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, build_key VARCHAR(255) NOT NULL, failed_runs INTEGER NOT NULL)');
        $this->addSql('INSERT INTO bamboo_nightly_build (id, build_key, failed_runs) SELECT id, build_key, failed_runs FROM __temp__bamboo_nightly_build');
        $this->addSql('DROP TABLE __temp__bamboo_nightly_build');
    }
}
