<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20260727220000_ShipUpgradeGoldOnlyFirstHalf extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ship upgrades: diamonds = 0 for first half of levels (skills/work/missions 1–25, hull 1–7)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "UPDATE ship_upgrade_level_cost SET diamonds = 0
             WHERE upgrade_type IN ('skills', 'work', 'missions') AND target_level <= 25"
        );
        $this->addSql(
            "UPDATE ship_upgrade_level_cost SET diamonds = 0
             WHERE upgrade_type = 'hull' AND target_level <= 7"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            "UPDATE ship_upgrade_level_cost
             SET diamonds = 40 + (target_level - 1) * 20
             WHERE upgrade_type IN ('skills', 'work', 'missions') AND target_level <= 25"
        );
        $this->addSql(
            "UPDATE ship_upgrade_level_cost
             SET diamonds = 40 + (target_level - 1) * 20
             WHERE upgrade_type = 'hull' AND target_level <= 7"
        );
    }
}
