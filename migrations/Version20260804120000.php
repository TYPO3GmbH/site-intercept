<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Utility\RepositoryUrlUtility;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Quarantined entries recorded the host of the clone url, while the check that
 * blocked them keys on the host of the composer.json url. Recompute the domain
 * of the existing rows from the push event they carry, so approving them
 * allowlists the host that is actually consulted.
 */
final class Version20260804120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Set the quarantined domain to the host of the composer.json url';
    }

    public function up(Schema $schema): void
    {
        $rows = $this->connection->fetchAllAssociative('SELECT id, domain, serialized_push_event FROM documentation_quarantine');
        foreach ($rows as $row) {
            try {
                $pushEvent = json_decode((string) $row['serialized_push_event'], true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                continue;
            }
            $urlToComposerFile = (string) ($pushEvent['urlToComposerFile'] ?? '');
            if ('' === $urlToComposerFile) {
                continue;
            }
            $domain = RepositoryUrlUtility::getNormalizedDomain($urlToComposerFile);
            if ('' === $domain || $domain === $row['domain']) {
                continue;
            }
            $this->connection->update('documentation_quarantine', ['domain' => $domain], ['id' => $row['id']]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('The previously stored domain can not be restored, it was derived from the clone url.');
    }

    public function isTransactional(): bool
    {
        return false;
    }
}
