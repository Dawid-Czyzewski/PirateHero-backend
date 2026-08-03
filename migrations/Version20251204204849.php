<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251204204849 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add energy refill columns to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE `user` ADD energy_refill_count INT NOT NULL DEFAULT 0, ADD last_energy_refill_date DATETIME DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE `user` DROP energy_refill_count, DROP last_energy_refill_date
        SQL);
    }
}
