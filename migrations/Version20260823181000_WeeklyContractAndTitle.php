<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260823181000_WeeklyContractAndTitle extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Weekly pirate contract table + weekly_corsair title';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_weekly_contract (
            id INT AUTO_INCREMENT NOT NULL,
            user_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
            week_start DATE NOT NULL,
            type VARCHAR(32) NOT NULL,
            target_value INT NOT NULL,
            progress INT NOT NULL,
            reward_claimed TINYINT(1) NOT NULL,
            INDEX IDX_WEEKLY_CONTRACT_USER (user_id),
            UNIQUE INDEX UNIQ_USER_WEEKLY_CONTRACT (user_id, week_start),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_weekly_contract ADD CONSTRAINT FK_WEEKLY_CONTRACT_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');

        $this->addSql("INSERT INTO player_title (code, name_key, description_key, unlock_type, unlock_value, unlock_dungeon_id, sort_order)
            VALUES ('weekly_corsair', 'titles.weekly_corsair.name', 'titles.weekly_corsair.unlockHint', 'MANUAL', NULL, NULL, 200)
            ON DUPLICATE KEY UPDATE name_key = VALUES(name_key)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM user_title WHERE player_title_id IN (SELECT id FROM player_title WHERE code = 'weekly_corsair')");
        $this->addSql("DELETE FROM player_title WHERE code = 'weekly_corsair'");
        $this->addSql('ALTER TABLE user_weekly_contract DROP FOREIGN KEY FK_WEEKLY_CONTRACT_USER');
        $this->addSql('DROP TABLE user_weekly_contract');
    }
}
