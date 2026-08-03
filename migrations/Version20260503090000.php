<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Renames legacy club_upgrade_pricing → ship_upgrade_pricing (fresh installs get this name from Version20260503000000).
 */
final class Version20260503090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename club_upgrade_pricing to ship_upgrade_pricing.';
    }

    public function up(Schema $schema): void
    {
        $db = $this->connection->fetchOne('SELECT DATABASE()');
        if (!\is_string($db) || $db === '') {
            return;
        }
        $club = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [$db, 'club_upgrade_pricing']
        );
        if ($club < 1) {
            return;
        }
        $this->addSql('RENAME TABLE club_upgrade_pricing TO ship_upgrade_pricing');
    }

    public function down(Schema $schema): void
    {
        // No-op: cannot safely reverse if ship_upgrade_pricing was created directly by migrated schema.
    }
}
