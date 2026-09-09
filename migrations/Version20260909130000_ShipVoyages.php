<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260909130000_ShipVoyages extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ship voyages table + UserActualActivity.ship_voyage_id';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE ship_voyage (
            id INT AUTO_INCREMENT NOT NULL,
            ship_id INT NOT NULL,
            started_by_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
            duration_seconds INT NOT NULL,
            gold_cost INT NOT NULL,
            base_gold INT NOT NULL,
            base_exp INT NOT NULL,
            enrolled_count INT NOT NULL,
            status VARCHAR(16) NOT NULL,
            started_at DATETIME NOT NULL,
            completed_at DATETIME DEFAULT NULL,
            INDEX IDX_SHIP_VOYAGE_SHIP (ship_id),
            INDEX IDX_SHIP_VOYAGE_STARTED_BY (started_by_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ship_voyage ADD CONSTRAINT FK_SHIP_VOYAGE_SHIP FOREIGN KEY (ship_id) REFERENCES ship (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ship_voyage ADD CONSTRAINT FK_SHIP_VOYAGE_STARTED_BY FOREIGN KEY (started_by_id) REFERENCES `user` (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE user_actual_activity ADD ship_voyage_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_actual_activity ADD CONSTRAINT FK_UAA_SHIP_VOYAGE FOREIGN KEY (ship_voyage_id) REFERENCES ship_voyage (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_UAA_SHIP_VOYAGE ON user_actual_activity (ship_voyage_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_actual_activity DROP FOREIGN KEY FK_UAA_SHIP_VOYAGE');
        $this->addSql('DROP INDEX IDX_UAA_SHIP_VOYAGE ON user_actual_activity');
        $this->addSql('ALTER TABLE user_actual_activity DROP ship_voyage_id');
        $this->addSql('ALTER TABLE ship_voyage DROP FOREIGN KEY FK_SHIP_VOYAGE_SHIP');
        $this->addSql('ALTER TABLE ship_voyage DROP FOREIGN KEY FK_SHIP_VOYAGE_STARTED_BY');
        $this->addSql('DROP TABLE ship_voyage');
    }
}
