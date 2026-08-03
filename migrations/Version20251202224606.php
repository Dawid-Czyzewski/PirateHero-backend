<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251202224606 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE club_fight_notification (id INT AUTO_INCREMENT NOT NULL, user_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', club_id INT NOT NULL, attacker_club_id INT NOT NULL, defender_club_id INT NOT NULL, fight_type VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', is_read TINYINT(1) NOT NULL, INDEX IDX_52FB0367A76ED395 (user_id), INDEX IDX_52FB036761190A32 (club_id), INDEX IDX_52FB036762C568CC (attacker_club_id), INDEX IDX_52FB0367AF379602 (defender_club_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE club_fight_notification ADD CONSTRAINT FK_52FB0367A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE club_fight_notification ADD CONSTRAINT FK_52FB036761190A32 FOREIGN KEY (club_id) REFERENCES club (id)');
        $this->addSql('ALTER TABLE club_fight_notification ADD CONSTRAINT FK_52FB036762C568CC FOREIGN KEY (attacker_club_id) REFERENCES club (id)');
        $this->addSql('ALTER TABLE club_fight_notification ADD CONSTRAINT FK_52FB0367AF379602 FOREIGN KEY (defender_club_id) REFERENCES club (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE club_fight_notification DROP FOREIGN KEY FK_52FB0367A76ED395');
        $this->addSql('ALTER TABLE club_fight_notification DROP FOREIGN KEY FK_52FB036761190A32');
        $this->addSql('ALTER TABLE club_fight_notification DROP FOREIGN KEY FK_52FB036762C568CC');
        $this->addSql('ALTER TABLE club_fight_notification DROP FOREIGN KEY FK_52FB0367AF379602');
        $this->addSql('DROP TABLE club_fight_notification');
    }
}
