<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260503000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Singleton ship_upgrade_pricing — parametry kosztów ulepszeń statku z bazy.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE ship_upgrade_pricing (
    id INT NOT NULL,
    base_gold INT NOT NULL,
    gold_step INT NOT NULL,
    base_diamonds INT NOT NULL,
    diamonds_step INT NOT NULL,
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);
        $this->addSql('INSERT INTO ship_upgrade_pricing (id, base_gold, gold_step, base_diamonds, diamonds_step) VALUES (1, 150, 30, 40, 20)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE ship_upgrade_pricing');
    }
}
