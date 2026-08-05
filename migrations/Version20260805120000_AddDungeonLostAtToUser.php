<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260805120000_AddDungeonLostAtToUser extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add global dungeon loss cooldown timestamp on user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD dungeon_lost_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP dungeon_lost_at');
    }
}
