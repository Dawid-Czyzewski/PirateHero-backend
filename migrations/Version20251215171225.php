<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251215171225 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create coupon and user_coupon tables for coupon system';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS coupon (
                id INT AUTO_INCREMENT NOT NULL,
                used_by_user_id CHAR(36) DEFAULT NULL COMMENT '(DC2Type:guid)',
                code VARCHAR(255) NOT NULL,
                type VARCHAR(255) NOT NULL,
                reward_type VARCHAR(255) NOT NULL,
                reward_value INT DEFAULT NULL,
                reward_data JSON DEFAULT NULL COMMENT '(DC2Type:json)',
                expires_at DATETIME DEFAULT NULL,
                created_at DATETIME NOT NULL,
                used_at DATETIME DEFAULT NULL,
                INDEX IDX_64BF3F0298DEAAC4 (used_by_user_id),
                UNIQUE INDEX UNIQ_COUPON_CODE (code),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS user_coupon (
                id INT AUTO_INCREMENT NOT NULL,
                user_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)',
                coupon_id INT NOT NULL,
                used_at DATETIME NOT NULL,
                reward_received JSON DEFAULT NULL COMMENT '(DC2Type:json)',
                INDEX IDX_1ED243FA76ED395 (user_id),
                INDEX IDX_1ED243F66C5951B (coupon_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);

        $this->addSql(<<<'SQL'
            SET @sql = (SELECT IF(
                (SELECT COUNT(*) FROM information_schema.table_constraints 
                 WHERE constraint_schema = DATABASE() 
                 AND constraint_name = 'FK_64BF3F0298DEAAC4') = 0,
                'ALTER TABLE coupon ADD CONSTRAINT FK_64BF3F0298DEAAC4 FOREIGN KEY (used_by_user_id) REFERENCES `user` (id)',
                'SELECT 1'
            ));
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        SQL);

        $this->addSql(<<<'SQL'
            SET @sql = (SELECT IF(
                (SELECT COUNT(*) FROM information_schema.table_constraints 
                 WHERE constraint_schema = DATABASE() 
                 AND constraint_name = 'FK_1ED243FA76ED395') = 0,
                'ALTER TABLE user_coupon ADD CONSTRAINT FK_1ED243FA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)',
                'SELECT 1'
            ));
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        SQL);

        $this->addSql(<<<'SQL'
            SET @sql = (SELECT IF(
                (SELECT COUNT(*) FROM information_schema.table_constraints 
                 WHERE constraint_schema = DATABASE() 
                 AND constraint_name = 'FK_1ED243F66C5951B') = 0,
                'ALTER TABLE user_coupon ADD CONSTRAINT FK_1ED243F66C5951B FOREIGN KEY (coupon_id) REFERENCES coupon (id)',
                'SELECT 1'
            ));
            PREPARE stmt FROM @sql;
            EXECUTE stmt;
            DEALLOCATE PREPARE stmt;
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            DROP TABLE IF EXISTS user_coupon
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE IF EXISTS coupon
        SQL);
    }
}
