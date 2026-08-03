<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260531140000_DungeonCompletionReward extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Track dungeon completion bonus claim on user_dungeon_progress';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_dungeon_progress ADD completion_reward_claimed TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_dungeon_progress DROP completion_reward_claimed');
    }
}
