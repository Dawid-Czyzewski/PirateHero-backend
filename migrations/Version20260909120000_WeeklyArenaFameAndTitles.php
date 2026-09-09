<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260909120000_WeeklyArenaFameAndTitles extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Weekly arena fame ladder table + fighter/champion/legend titles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_weekly_arena_fame (
            id INT AUTO_INCREMENT NOT NULL,
            user_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
            week_start DATE NOT NULL,
            fame_earned INT NOT NULL,
            claimed_tier1 TINYINT(1) NOT NULL,
            claimed_tier2 TINYINT(1) NOT NULL,
            claimed_tier3 TINYINT(1) NOT NULL,
            INDEX IDX_WEEKLY_ARENA_FAME_USER (user_id),
            UNIQUE INDEX UNIQ_USER_WEEKLY_ARENA_FAME (user_id, week_start),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_weekly_arena_fame ADD CONSTRAINT FK_WEEKLY_ARENA_FAME_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');

        $this->addSql("INSERT INTO player_title (code, name_key, description_key, unlock_type, unlock_value, unlock_dungeon_id, sort_order)
            VALUES
            ('weekly_arena_fighter', 'titles.weekly_arena_fighter.name', 'titles.weekly_arena_fighter.unlockHint', 'MANUAL', NULL, NULL, 201),
            ('weekly_arena_champion', 'titles.weekly_arena_champion.name', 'titles.weekly_arena_champion.unlockHint', 'MANUAL', NULL, NULL, 202),
            ('weekly_arena_legend', 'titles.weekly_arena_legend.name', 'titles.weekly_arena_legend.unlockHint', 'MANUAL', NULL, NULL, 203)
            ON DUPLICATE KEY UPDATE name_key = VALUES(name_key)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM user_title WHERE player_title_id IN (SELECT id FROM player_title WHERE code IN ('weekly_arena_fighter','weekly_arena_champion','weekly_arena_legend'))");
        $this->addSql("DELETE FROM player_title WHERE code IN ('weekly_arena_fighter','weekly_arena_champion','weekly_arena_legend')");
        $this->addSql('ALTER TABLE user_weekly_arena_fame DROP FOREIGN KEY FK_WEEKLY_ARENA_FAME_USER');
        $this->addSql('DROP TABLE user_weekly_arena_fame');
    }
}
