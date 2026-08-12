<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260812140000_DungeonHardModeDifficulty extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Dungeon hard mode: difficulty on user_dungeon_progress + loss cooldown duration on user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE user_dungeon_progress ADD difficulty VARCHAR(16) NOT NULL DEFAULT 'normal'");
        $this->addSql('ALTER TABLE user_dungeon_progress DROP INDEX UNIQ_USER_DUNGEON');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_USER_DUNGEON_DIFFICULTY ON user_dungeon_progress (user_id, dungeon_id, difficulty)');
        $this->addSql('ALTER TABLE `user` ADD dungeon_loss_cooldown_seconds INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP dungeon_loss_cooldown_seconds');
        $this->addSql('ALTER TABLE user_dungeon_progress DROP INDEX UNIQ_USER_DUNGEON_DIFFICULTY');
        $this->addSql('DELETE FROM user_dungeon_progress WHERE difficulty != \'normal\'');
        $this->addSql('ALTER TABLE user_dungeon_progress DROP difficulty');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_USER_DUNGEON ON user_dungeon_progress (user_id, dungeon_id)');
    }
}
