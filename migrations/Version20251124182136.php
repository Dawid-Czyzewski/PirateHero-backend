<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251124182136 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE club_join_request (id INT AUTO_INCREMENT NOT NULL, user_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', club_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', responded_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', approved TINYINT(1) DEFAULT NULL, INDEX IDX_93864C0FA76ED395 (user_id), INDEX IDX_93864C0F61190A32 (club_id), UNIQUE INDEX unique_user_club_request (user_id, club_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE club_join_request ADD CONSTRAINT FK_93864C0FA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE club_join_request ADD CONSTRAINT FK_93864C0F61190A32 FOREIGN KEY (club_id) REFERENCES club (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE club_join_request DROP FOREIGN KEY FK_93864C0FA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE club_join_request DROP FOREIGN KEY FK_93864C0F61190A32
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE club_join_request
        SQL);
    }
}
