<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Item stats for clients are derived from strong/agility/critical/health columns only. */
final class Version20260408130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop item_statistics.shop_stats; use scalar stat columns only.';
    }

    public function up(Schema $schema): void
    {
        $this->connection->executeStatement('ALTER TABLE item_statistics DROP COLUMN shop_stats');
    }

    public function down(Schema $schema): void
    {
        $this->connection->executeStatement('ALTER TABLE item_statistics ADD shop_stats JSON DEFAULT NULL');
    }
}
