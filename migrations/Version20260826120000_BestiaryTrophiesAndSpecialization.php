<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260826120000_BestiaryTrophiesAndSpecialization extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Bestiary trophies table + wearable specialization + milestone titles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_bestiary_trophy (
            id INT AUTO_INCREMENT NOT NULL,
            user_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
            trophy_code VARCHAR(64) NOT NULL,
            unlocked_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            reward_claimed TINYINT(1) NOT NULL DEFAULT 0,
            INDEX IDX_BESTIARY_TROPHY_USER (user_id),
            UNIQUE INDEX UNIQ_USER_BESTIARY_TROPHY (user_id, trophy_code),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_bestiary_trophy ADD CONSTRAINT FK_BESTIARY_TROPHY_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE wearable_item ADD specialization VARCHAR(32) DEFAULT NULL');

        $this->addSql("INSERT INTO player_title (code, name_key, description_key, unlock_type, unlock_value, unlock_dungeon_id, sort_order)
            VALUES
            ('bestiary_scout', 'titles.bestiary_scout.name', 'titles.bestiary_scout.unlockHint', 'BESTIARY_COMPLETE', 13, NULL, 201),
            ('bestiary_tracker', 'titles.bestiary_tracker.name', 'titles.bestiary_tracker.unlockHint', 'BESTIARY_COMPLETE', 25, NULL, 202),
            ('bestiary_naturalist', 'titles.bestiary_naturalist.name', 'titles.bestiary_naturalist.unlockHint', 'BESTIARY_COMPLETE', 38, NULL, 203),
            ('bestiary_archivist', 'titles.bestiary_archivist.name', 'titles.bestiary_archivist.unlockHint', 'MANUAL', NULL, NULL, 204)
            ON DUPLICATE KEY UPDATE name_key = VALUES(name_key)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM user_title WHERE player_title_id IN (SELECT id FROM player_title WHERE code IN ('bestiary_scout','bestiary_tracker','bestiary_naturalist','bestiary_archivist'))");
        $this->addSql("DELETE FROM player_title WHERE code IN ('bestiary_scout','bestiary_tracker','bestiary_naturalist','bestiary_archivist')");
        $this->addSql('ALTER TABLE wearable_item DROP specialization');
        $this->addSql('ALTER TABLE user_bestiary_trophy DROP FOREIGN KEY FK_BESTIARY_TROPHY_USER');
        $this->addSql('DROP TABLE user_bestiary_trophy');
    }
}
