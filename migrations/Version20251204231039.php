<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251204231039 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE user_refill (id INT AUTO_INCREMENT NOT NULL, user_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', type VARCHAR(255) NOT NULL, refill_count INT NOT NULL, last_refill_date DATETIME DEFAULT NULL, INDEX IDX_17AFBFC1A76ED395 (user_id), UNIQUE INDEX user_refill_unique (user_id, type), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_refill ADD CONSTRAINT FK_17AFBFC1A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);

        $this->addSql(<<<'SQL'
            INSERT INTO user_refill (user_id, type, refill_count, last_refill_date)
            SELECT user_id, 'ENERGY', refill_count, last_refill_date
            FROM user_energy_refill
        SQL);

        $this->addSql(<<<'SQL'
            INSERT INTO user_refill (user_id, type, refill_count, last_refill_date)
            SELECT DISTINCT u.id, 'TRAINING', 0, NULL
            FROM `user` u
            WHERE NOT EXISTS (
                SELECT 1 FROM user_refill ur WHERE ur.user_id = u.id AND ur.type = 'TRAINING'
            )
        SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO user_refill (user_id, type, refill_count, last_refill_date)
            SELECT DISTINCT u.id, 'FIGHT', 0, NULL
            FROM `user` u
            WHERE NOT EXISTS (
                SELECT 1 FROM user_refill ur WHERE ur.user_id = u.id AND ur.type = 'FIGHT'
            )
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE user_energy_refill DROP FOREIGN KEY FK_7DC5A881A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_energy_refill
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE user_energy_refill (id INT AUTO_INCREMENT NOT NULL, user_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', refill_count INT NOT NULL, last_refill_date DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_7DC5A881A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_energy_refill ADD CONSTRAINT FK_7DC5A881A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);

        $this->addSql(<<<'SQL'
            INSERT INTO user_energy_refill (user_id, refill_count, last_refill_date)
            SELECT user_id, refill_count, last_refill_date
            FROM user_refill
            WHERE type = 'ENERGY'
        SQL);

        $this->addSql(<<<'SQL'
            ALTER TABLE user_refill DROP FOREIGN KEY FK_17AFBFC1A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_refill
        SQL);
    }
}
