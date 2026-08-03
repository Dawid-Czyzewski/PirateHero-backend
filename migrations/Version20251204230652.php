<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251204230652 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE user_energy_refill (id INT AUTO_INCREMENT NOT NULL, user_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', refill_count INT NOT NULL, last_refill_date DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_7DC5A881A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_energy_refill ADD CONSTRAINT FK_7DC5A881A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);

        $this->addSql(<<<'SQL'
            INSERT INTO user_energy_refill (user_id, refill_count, last_refill_date)
            SELECT id, COALESCE(energy_refill_count, 0), last_energy_refill_date
            FROM `user`
            WHERE energy_refill_count IS NOT NULL OR last_energy_refill_date IS NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE `user` DROP energy_refill_count, DROP last_energy_refill_date
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE `user` ADD energy_refill_count INT DEFAULT 0 NOT NULL, ADD last_energy_refill_date DATETIME DEFAULT NULL
        SQL);

        $this->addSql(<<<'SQL'
            UPDATE `user` u
            INNER JOIN user_energy_refill uer ON u.id = uer.user_id
            SET u.energy_refill_count = uer.refill_count,
                u.last_energy_refill_date = uer.last_refill_date
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE user_energy_refill DROP FOREIGN KEY FK_7DC5A881A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_energy_refill
        SQL);
    }
}
