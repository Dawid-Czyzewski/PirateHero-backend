<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260620120000_UserBestiaryEntry extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user_bestiary_entry for dungeon enemy discovery tracking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_bestiary_entry (
            id INT AUTO_INCREMENT NOT NULL,
            user_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
            dungeon_id VARCHAR(32) NOT NULL,
            stage INT NOT NULL,
            defeated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_USER_BESTIARY_ENTRY_USER (user_id),
            UNIQUE INDEX UNIQ_USER_BESTIARY_DUNGEON_STAGE (user_id, dungeon_id, stage),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_bestiary_entry ADD CONSTRAINT FK_USER_BESTIARY_ENTRY_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');

        $this->addSql('INSERT INTO user_bestiary_entry (user_id, dungeon_id, stage, defeated_at)
            SELECT udp.user_id, udp.dungeon_id, s.stage, NULL
            FROM user_dungeon_progress udp
            INNER JOIN (
                SELECT 1 AS stage UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
                UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
            ) s ON s.stage <= udp.cleared_stage
            WHERE udp.dungeon_id IN (\'krypta\', \'kraken\')
              AND udp.cleared_stage > 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_bestiary_entry DROP FOREIGN KEY FK_USER_BESTIARY_ENTRY_USER');
        $this->addSql('DROP TABLE user_bestiary_entry');
    }
}
