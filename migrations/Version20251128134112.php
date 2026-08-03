<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251128134112 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create clubs_fight, clubs_fight_member, and clubs_fight_move tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE clubs_fight (id INT AUTO_INCREMENT NOT NULL, attacker_club_id INT NOT NULL, defender_club_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', result VARCHAR(255) NOT NULL, score_attacker_score INT NOT NULL, score_defender_score INT NOT NULL, INDEX IDX_8A1B2C3D4E5F6A7B (attacker_club_id), INDEX IDX_8A1B2C3D4E5F6A7C (defender_club_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE clubs_fight_member (id INT AUTO_INCREMENT NOT NULL, clubs_fight_id INT NOT NULL, user_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', is_defeated TINYINT(1) NOT NULL, initial_health INT NOT NULL, current_health INT NOT NULL, is_attacker_side TINYINT(1) NOT NULL, INDEX IDX_9B1C2D3E4F5A6B7C (clubs_fight_id), INDEX IDX_9B1C2D3E4F5A6B7D (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE clubs_fight_move (id INT AUTO_INCREMENT NOT NULL, clubs_fight_id INT NOT NULL, player_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', target_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', result VARCHAR(255) NOT NULL, move_number INT NOT NULL, damage INT NOT NULL, target_health_after INT NOT NULL, INDEX IDX_A1B2C3D4E5F6A7B8 (clubs_fight_id), INDEX IDX_A1B2C3D4E5F6A7B9 (player_id), INDEX IDX_A1B2C3D4E5F6A7BA (target_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE clubs_fight ADD CONSTRAINT FK_8A1B2C3D4E5F6A7B FOREIGN KEY (attacker_club_id) REFERENCES club (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE clubs_fight ADD CONSTRAINT FK_8A1B2C3D4E5F6A7C FOREIGN KEY (defender_club_id) REFERENCES club (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE clubs_fight_member ADD CONSTRAINT FK_9B1C2D3E4F5A6B7C FOREIGN KEY (clubs_fight_id) REFERENCES clubs_fight (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE clubs_fight_member ADD CONSTRAINT FK_9B1C2D3E4F5A6B7D FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE clubs_fight_move ADD CONSTRAINT FK_A1B2C3D4E5F6A7B8 FOREIGN KEY (clubs_fight_id) REFERENCES clubs_fight (id) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE clubs_fight_move ADD CONSTRAINT FK_A1B2C3D4E5F6A7B9 FOREIGN KEY (player_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE clubs_fight_move ADD CONSTRAINT FK_A1B2C3D4E5F6A7BA FOREIGN KEY (target_id) REFERENCES `user` (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE clubs_fight_move DROP FOREIGN KEY FK_A1B2C3D4E5F6A7BA
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE clubs_fight_move DROP FOREIGN KEY FK_A1B2C3D4E5F6A7B9
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE clubs_fight_move DROP FOREIGN KEY FK_A1B2C3D4E5F6A7B8
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE clubs_fight_member DROP FOREIGN KEY FK_9B1C2D3E4F5A6B7D
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE clubs_fight_member DROP FOREIGN KEY FK_9B1C2D3E4F5A6B7C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE clubs_fight DROP FOREIGN KEY FK_8A1B2C3D4E5F6A7C
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE clubs_fight DROP FOREIGN KEY FK_8A1B2C3D4E5F6A7B
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE clubs_fight_move
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE clubs_fight_member
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE clubs_fight
        SQL);
    }
}
