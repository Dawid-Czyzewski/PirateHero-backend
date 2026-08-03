<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251124205308 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE club_invitation (id INT AUTO_INCREMENT NOT NULL, user_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', club_id INT NOT NULL, inviter_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', accepted TINYINT(1) DEFAULT NULL, INDEX IDX_B8DB2F4FA76ED395 (user_id), INDEX IDX_B8DB2F4F61190A32 (club_id), INDEX IDX_B8DB2F4FB79F4F04 (inviter_id), UNIQUE INDEX unique_user_club_invitation (user_id, club_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE club_invitation ADD CONSTRAINT FK_B8DB2F4FA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE club_invitation ADD CONSTRAINT FK_B8DB2F4F61190A32 FOREIGN KEY (club_id) REFERENCES club (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE club_invitation ADD CONSTRAINT FK_B8DB2F4FB79F4F04 FOREIGN KEY (inviter_id) REFERENCES `user` (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE club_invitation DROP FOREIGN KEY FK_B8DB2F4FA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE club_invitation DROP FOREIGN KEY FK_B8DB2F4F61190A32
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE club_invitation DROP FOREIGN KEY FK_B8DB2F4FB79F4F04
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE club_invitation
        SQL);
    }
}
