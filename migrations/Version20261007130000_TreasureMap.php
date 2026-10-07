<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261007130000_TreasureMap extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Weekly treasure map progress table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_treasure_map (
            id INT AUTO_INCREMENT NOT NULL,
            user_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
            week_start DATE NOT NULL,
            current_step INT NOT NULL,
            step_progress JSON NOT NULL,
            completed TINYINT(1) NOT NULL,
            chest_claimed TINYINT(1) NOT NULL,
            INDEX IDX_TREASURE_MAP_USER (user_id),
            UNIQUE INDEX UNIQ_USER_TREASURE_MAP_WEEK (user_id, week_start),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_treasure_map ADD CONSTRAINT FK_TREASURE_MAP_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_treasure_map DROP FOREIGN KEY FK_TREASURE_MAP_USER');
        $this->addSql('DROP TABLE user_treasure_map');
    }
}
