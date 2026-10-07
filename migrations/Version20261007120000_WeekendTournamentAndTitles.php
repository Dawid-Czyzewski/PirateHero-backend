<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261007120000_WeekendTournamentAndTitles extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Weekend arena tournament table + champion/gladiator titles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_weekend_tournament (
            id INT AUTO_INCREMENT NOT NULL,
            user_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
            event_start DATE NOT NULL,
            points INT NOT NULL,
            reward_claimed TINYINT(1) NOT NULL,
            INDEX IDX_WEEKEND_TOURNAMENT_USER (user_id),
            UNIQUE INDEX UNIQ_USER_WEEKEND_TOURNAMENT (user_id, event_start),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_weekend_tournament ADD CONSTRAINT FK_WEEKEND_TOURNAMENT_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');

        $this->addSql("INSERT INTO player_title (code, name_key, description_key, unlock_type, unlock_value, unlock_dungeon_id, sort_order)
            VALUES
            ('weekend_arena_champion', 'titles.weekend_arena_champion.name', 'titles.weekend_arena_champion.unlockHint', 'MANUAL', NULL, NULL, 205),
            ('weekend_gladiator', 'titles.weekend_gladiator.name', 'titles.weekend_gladiator.unlockHint', 'MANUAL', NULL, NULL, 206)
            ON DUPLICATE KEY UPDATE name_key = VALUES(name_key)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM user_title WHERE player_title_id IN (SELECT id FROM player_title WHERE code IN ('weekend_arena_champion','weekend_gladiator'))");
        $this->addSql("DELETE FROM player_title WHERE code IN ('weekend_arena_champion','weekend_gladiator')");
        $this->addSql('ALTER TABLE user_weekend_tournament DROP FOREIGN KEY FK_WEEKEND_TOURNAMENT_USER');
        $this->addSql('DROP TABLE user_weekend_tournament');
    }
}
