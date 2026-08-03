<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260528120000_AddUserDungeonProgress extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user_dungeon_progress for dungeon stage progression';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_dungeon_progress (
            id INT AUTO_INCREMENT NOT NULL,
            user_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
            dungeon_id VARCHAR(32) NOT NULL,
            cleared_stage INT NOT NULL,
            INDEX IDX_USER_DUNGEON_PROGRESS_USER (user_id),
            UNIQUE INDEX UNIQ_USER_DUNGEON (user_id, dungeon_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_dungeon_progress ADD CONSTRAINT FK_USER_DUNGEON_PROGRESS_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_dungeon_progress DROP FOREIGN KEY FK_USER_DUNGEON_PROGRESS_USER');
        $this->addSql('DROP TABLE user_dungeon_progress');
    }
}
