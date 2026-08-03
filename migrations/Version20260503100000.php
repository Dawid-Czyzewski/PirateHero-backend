<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Koszty ulepszeń: tabela ship_upgrade_level_cost (osobno typ + poziom).
 * Zastępuje singleton ship_upgrade_pricing (po migracji Version20260503000000).
 */
final class Version20260503100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'ship_upgrade_level_cost per type & level; drop ship_upgrade_pricing.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
CREATE TABLE ship_upgrade_level_cost (
    upgrade_type VARCHAR(20) NOT NULL,
    target_level INT NOT NULL,
    gold INT NOT NULL,
    diamonds INT NOT NULL,
    PRIMARY KEY(upgrade_type, target_level)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);

        $baseGold = 150;
        $goldStep = 30;
        $baseDiamonds = 40;
        $diamondsStep = 20;

        try {
            $row = $this->connection->fetchAssociative(
                'SELECT base_gold, gold_step, base_diamonds, diamonds_step FROM ship_upgrade_pricing WHERE id = 1'
            );
            if (\is_array($row)) {
                $baseGold = (int) ($row['base_gold'] ?? $baseGold);
                $goldStep = (int) ($row['gold_step'] ?? $goldStep);
                $baseDiamonds = (int) ($row['base_diamonds'] ?? $baseDiamonds);
                $diamondsStep = (int) ($row['diamonds_step'] ?? $diamondsStep);
            }
        } catch (\Throwable) {
        }

        foreach (['skills', 'work', 'missions'] as $type) {
            for ($L = 1; $L <= 50; ++$L) {
                $g = $baseGold + ($L - 1) * $goldStep;
                $d = $baseDiamonds + ($L - 1) * $diamondsStep;
                $this->addSql(
                    'INSERT INTO ship_upgrade_level_cost (upgrade_type, target_level, gold, diamonds) VALUES (?, ?, ?, ?)',
                    [$type, $L, $g, $d]
                );
            }
        }

        for ($L = 1; $L <= 15; ++$L) {
            $g = $baseGold + ($L - 1) * $goldStep;
            $d = $baseDiamonds + ($L - 1) * $diamondsStep;
            $this->addSql(
                'INSERT INTO ship_upgrade_level_cost (upgrade_type, target_level, gold, diamonds) VALUES (?, ?, ?, ?)',
                ['hull', $L, $g, $d]
            );
        }

        $this->addSql('DROP TABLE IF EXISTS ship_upgrade_pricing');
        $this->addSql('DROP TABLE IF EXISTS club_upgrade_pricing');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS ship_upgrade_level_cost');
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
}
