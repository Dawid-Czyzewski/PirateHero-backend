<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251106190005 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE club (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, internal_notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE club_member (id INT AUTO_INCREMENT NOT NULL, user_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', club_id INT NOT NULL, role VARCHAR(20) NOT NULL, joined_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_552B46F2A76ED395 (user_id), INDEX IDX_552B46F261190A32 (club_id), UNIQUE INDEX unique_user_club (user_id, club_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE club_message (id INT AUTO_INCREMENT NOT NULL, author_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', club_id INT NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_BBF595FDF675F31B (author_id), INDEX IDX_BBF595FD61190A32 (club_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE club_member ADD CONSTRAINT FK_552B46F2A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE club_member ADD CONSTRAINT FK_552B46F261190A32 FOREIGN KEY (club_id) REFERENCES club (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE club_message ADD CONSTRAINT FK_BBF595FDF675F31B FOREIGN KEY (author_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE club_message ADD CONSTRAINT FK_BBF595FD61190A32 FOREIGN KEY (club_id) REFERENCES club (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE club_member DROP FOREIGN KEY FK_552B46F2A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE club_member DROP FOREIGN KEY FK_552B46F261190A32
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE club_message DROP FOREIGN KEY FK_BBF595FDF675F31B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE club_message DROP FOREIGN KEY FK_BBF595FD61190A32
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE club
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE club_member
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE club_message
        SQL);
    }
}
