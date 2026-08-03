<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260516120000_RemoveLeagueSystem extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove league PvP tables (league_battle, user_league, league_opponent).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE league_battle DROP FOREIGN KEY FK_DC546F427F656CDC');
        $this->addSql('ALTER TABLE league_battle DROP FOREIGN KEY FK_DC546F42A76ED395');
        $this->addSql('DROP INDEX idx_league_battle_user_won ON league_battle');
        $this->addSql('DROP INDEX idx_league_battle_user_opponent_last_battle ON league_battle');
        $this->addSql('ALTER TABLE user_league DROP FOREIGN KEY FK_5BE6D825A76ED395');
        $this->addSql('DROP TABLE league_battle');
        $this->addSql('DROP TABLE user_league');
        $this->addSql('DROP TABLE league_opponent');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE league_opponent (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, league_number INT NOT NULL, position INT NOT NULL, level INT NOT NULL, health_points INT NOT NULL, strong_points INT NOT NULL, agility_points INT NOT NULL, critical_chance_points INT NOT NULL, required_level INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE UNIQUE INDEX unique_league_position ON league_opponent (league_number, position)');
        $this->addSql('CREATE TABLE user_league (id INT AUTO_INCREMENT NOT NULL, user_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', current_league INT DEFAULT NULL, UNIQUE INDEX UNIQ_5BE6D825A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_league ADD CONSTRAINT FK_5BE6D825A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('CREATE TABLE league_battle (id INT AUTO_INCREMENT NOT NULL, user_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', opponent_id INT NOT NULL, won TINYINT(1) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', last_battle_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_DC546F42A76ED395 (user_id), INDEX IDX_DC546F427F656CDC (opponent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE league_battle ADD CONSTRAINT FK_DC546F42A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE league_battle ADD CONSTRAINT FK_DC546F427F656CDC FOREIGN KEY (opponent_id) REFERENCES league_opponent (id)');
        $this->addSql('CREATE INDEX idx_league_battle_user_opponent_last_battle ON league_battle (user_id, opponent_id, last_battle_at)');
        $this->addSql('CREATE INDEX idx_league_battle_user_won ON league_battle (user_id, won)');
    }
}
