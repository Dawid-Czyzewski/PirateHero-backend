<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ship hull upgrades: crew capacity = 10 base + hull_upgrade (max hull level 15).
 */
final class Version20260428220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ship.hull_upgrade; sync max_members to 10 + hull (backfill from existing max_members).';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform()->getName();
        $this->skipIf(
            !str_contains($platform, 'mysql') && !str_contains($platform, 'mariadb'),
            'Hull upgrade migration: MySQL/MariaDB only.'
        );

        $this->addSql('ALTER TABLE ship ADD hull_upgrade INT NOT NULL DEFAULT 0');
        $this->addSql('UPDATE ship SET hull_upgrade = LEAST(15, GREATEST(0, max_members - 10))');
        $this->addSql('UPDATE ship SET max_members = 10 + hull_upgrade');
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform()->getName();
        $this->skipIf(
            !str_contains($platform, 'mysql') && !str_contains($platform, 'mariadb'),
            'Hull upgrade migration: MySQL/MariaDB only.'
        );

        $this->addSql('ALTER TABLE ship DROP hull_upgrade');
    }
}
