<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Oddziela inteligencję przedmiotu (mitigacja) od szczęścia / krytyka (critical_chance_points). */
final class Version20260408170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add item_statistics.intelligence_points (gear intelligence vs crit luck).';
    }

    public function up(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform()->getName();
        if (str_contains($platform, 'mysql') || str_contains($platform, 'mariadb')) {
            $this->addSql('ALTER TABLE item_statistics ADD intelligence_points INT NOT NULL DEFAULT 0');
        } else {
            $this->addSql('ALTER TABLE item_statistics ADD COLUMN intelligence_points INTEGER NOT NULL DEFAULT 0');
        }
    }

    public function down(Schema $schema): void
    {
        $platform = $this->connection->getDatabasePlatform()->getName();
        if (str_contains($platform, 'mysql') || str_contains($platform, 'mariadb')) {
            $this->addSql('ALTER TABLE item_statistics DROP intelligence_points');
        } else {
            $this->addSql('ALTER TABLE item_statistics DROP COLUMN intelligence_points');
        }
    }
}
