<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260630120000_PlayerTitle extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add player_title catalog, user_title unlocks, and user.equipped_title_id';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE player_title (
            id INT AUTO_INCREMENT NOT NULL,
            code VARCHAR(64) NOT NULL,
            name_key VARCHAR(128) NOT NULL,
            description_key VARCHAR(128) NOT NULL,
            unlock_type VARCHAR(32) NOT NULL,
            unlock_value INT DEFAULT NULL,
            unlock_dungeon_id VARCHAR(32) DEFAULT NULL,
            sort_order INT NOT NULL,
            UNIQUE INDEX UNIQ_PLAYER_TITLE_CODE (code),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE user_title (
            id INT AUTO_INCREMENT NOT NULL,
            user_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
            player_title_id INT NOT NULL,
            unlocked_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_USER_TITLE_USER (user_id),
            INDEX IDX_USER_TITLE_PLAYER_TITLE (player_title_id),
            UNIQUE INDEX UNIQ_USER_PLAYER_TITLE (user_id, player_title_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE user_title ADD CONSTRAINT FK_USER_TITLE_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_title ADD CONSTRAINT FK_USER_TITLE_PLAYER_TITLE FOREIGN KEY (player_title_id) REFERENCES player_title (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE `user` ADD equipped_title_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_USER_EQUIPPED_TITLE FOREIGN KEY (equipped_title_id) REFERENCES player_title (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_USER_EQUIPPED_TITLE ON `user` (equipped_title_id)');

        $this->addSql("INSERT INTO player_title (code, name_key, description_key, unlock_type, unlock_value, unlock_dungeon_id, sort_order) VALUES
            ('rookie', 'titles.rookie.name', 'titles.rookie.unlockHint', 'GAME_START', NULL, NULL, 1),
            ('crypt_hunter', 'titles.crypt_hunter.name', 'titles.crypt_hunter.unlockHint', 'DUNGEON_COMPLETED', NULL, 'krypta', 2),
            ('kraken_slayer', 'titles.kraken_slayer.name', 'titles.kraken_slayer.unlockHint', 'DUNGEON_COMPLETED', NULL, 'kraken', 3),
            ('veteran', 'titles.veteran.name', 'titles.veteran.unlockHint', 'LEVEL_REACHED', 25, NULL, 4),
            ('rich_captain', 'titles.rich_captain.name', 'titles.rich_captain.unlockHint', 'GOLD_BALANCE', 10000, NULL, 5)");

        $this->addSql("INSERT INTO user_title (user_id, player_title_id, unlocked_at)
            SELECT u.id, pt.id, NOW()
            FROM `user` u
            CROSS JOIN player_title pt
            WHERE pt.code = 'rookie'");

        $this->addSql("INSERT INTO user_title (user_id, player_title_id, unlocked_at)
            SELECT DISTINCT udp.user_id, pt.id, NOW()
            FROM user_dungeon_progress udp
            INNER JOIN player_title pt ON pt.code = 'crypt_hunter' AND pt.unlock_dungeon_id = 'krypta'
            WHERE udp.dungeon_id = 'krypta' AND udp.cleared_stage >= 10
            AND NOT EXISTS (
                SELECT 1 FROM user_title ut
                WHERE ut.user_id = udp.user_id AND ut.player_title_id = pt.id
            )");

        $this->addSql("INSERT INTO user_title (user_id, player_title_id, unlocked_at)
            SELECT DISTINCT udp.user_id, pt.id, NOW()
            FROM user_dungeon_progress udp
            INNER JOIN player_title pt ON pt.code = 'kraken_slayer' AND pt.unlock_dungeon_id = 'kraken'
            WHERE udp.dungeon_id = 'kraken' AND udp.cleared_stage >= 10
            AND NOT EXISTS (
                SELECT 1 FROM user_title ut
                WHERE ut.user_id = udp.user_id AND ut.player_title_id = pt.id
            )");

        $this->addSql("INSERT INTO user_title (user_id, player_title_id, unlocked_at)
            SELECT u.id, pt.id, NOW()
            FROM `user` u
            INNER JOIN level l ON u.level_id = l.id
            INNER JOIN player_title pt ON pt.code = 'veteran'
            WHERE CAST(l.name AS UNSIGNED) >= 25
            AND NOT EXISTS (
                SELECT 1 FROM user_title ut
                WHERE ut.user_id = u.id AND ut.player_title_id = pt.id
            )");

        $this->addSql("INSERT INTO user_title (user_id, player_title_id, unlocked_at)
            SELECT u.id, pt.id, NOW()
            FROM `user` u
            INNER JOIN player_title pt ON pt.code = 'rich_captain'
            WHERE u.gold >= 10000
            AND NOT EXISTS (
                SELECT 1 FROM user_title ut
                WHERE ut.user_id = u.id AND ut.player_title_id = pt.id
            )");

        $this->addSql("UPDATE `user` u
            INNER JOIN player_title pt ON pt.code = 'rookie'
            SET u.equipped_title_id = pt.id
            WHERE u.equipped_title_id IS NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_USER_EQUIPPED_TITLE');
        $this->addSql('DROP INDEX IDX_USER_EQUIPPED_TITLE ON `user`');
        $this->addSql('ALTER TABLE `user` DROP equipped_title_id');
        $this->addSql('ALTER TABLE user_title DROP FOREIGN KEY FK_USER_TITLE_USER');
        $this->addSql('ALTER TABLE user_title DROP FOREIGN KEY FK_USER_TITLE_PLAYER_TITLE');
        $this->addSql('DROP TABLE user_title');
        $this->addSql('DROP TABLE player_title');
    }
}
