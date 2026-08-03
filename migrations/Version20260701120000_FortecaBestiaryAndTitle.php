<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260701120000_FortecaBestiaryAndTitle extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add fortress_raider title and backfill Forteca bestiary entries for existing dungeon progress';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO player_title (code, name_key, description_key, unlock_type, unlock_value, unlock_dungeon_id, sort_order) VALUES
            ('fortress_raider', 'titles.fortress_raider.name', 'titles.fortress_raider.unlockHint', 'DUNGEON_COMPLETED', NULL, 'forteca', 6)");

        $this->addSql("INSERT INTO user_title (user_id, player_title_id, unlocked_at)
            SELECT DISTINCT udp.user_id, pt.id, NOW()
            FROM user_dungeon_progress udp
            INNER JOIN player_title pt ON pt.code = 'fortress_raider' AND pt.unlock_dungeon_id = 'forteca'
            WHERE udp.dungeon_id = 'forteca' AND udp.cleared_stage >= 10
            AND NOT EXISTS (
                SELECT 1 FROM user_title ut
                WHERE ut.user_id = udp.user_id AND ut.player_title_id = pt.id
            )");

        $this->addSql("INSERT INTO user_bestiary_entry (user_id, dungeon_id, stage, defeated_at)
            SELECT udp.user_id, udp.dungeon_id, s.stage, NULL
            FROM user_dungeon_progress udp
            INNER JOIN (
                SELECT 1 AS stage UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
                UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
            ) s ON s.stage <= udp.cleared_stage
            WHERE udp.dungeon_id = 'forteca'
              AND udp.cleared_stage > 0
              AND NOT EXISTS (
                SELECT 1 FROM user_bestiary_entry ube
                WHERE ube.user_id = udp.user_id
                  AND ube.dungeon_id = udp.dungeon_id
                  AND ube.stage = s.stage
              )");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM user_title WHERE player_title_id IN (SELECT id FROM player_title WHERE code = 'fortress_raider')");
        $this->addSql("DELETE FROM user_bestiary_entry WHERE dungeon_id = 'forteca'");
        $this->addSql("DELETE FROM player_title WHERE code = 'fortress_raider'");
    }
}
