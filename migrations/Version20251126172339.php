<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251126172339 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE club_removal_notification (id INT AUTO_INCREMENT NOT NULL, user_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', club_id INT NOT NULL, remover_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', is_read TINYINT(1) NOT NULL, INDEX IDX_7CAFEA82A76ED395 (user_id), INDEX IDX_7CAFEA8261190A32 (club_id), INDEX IDX_7CAFEA82E54D128A (remover_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE club_removal_notification ADD CONSTRAINT FK_7CAFEA82A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE club_removal_notification ADD CONSTRAINT FK_7CAFEA8261190A32 FOREIGN KEY (club_id) REFERENCES club (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE club_removal_notification ADD CONSTRAINT FK_7CAFEA82E54D128A FOREIGN KEY (remover_id) REFERENCES `user` (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE club_removal_notification DROP FOREIGN KEY FK_7CAFEA82A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE club_removal_notification DROP FOREIGN KEY FK_7CAFEA8261190A32
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE club_removal_notification DROP FOREIGN KEY FK_7CAFEA82E54D128A
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE club_removal_notification
        SQL);
    }
}
