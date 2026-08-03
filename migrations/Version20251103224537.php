<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251103224537 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            SET FOREIGN_KEY_CHECKS = 0
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE IF EXISTS booster
        SQL);
        $this->addSql(<<<'SQL'
            SET FOREIGN_KEY_CHECKS = 1
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE booster_template (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, type VARCHAR(255) NOT NULL, effect_amount INT NOT NULL, base_price_gold INT NOT NULL, base_price_premium INT NOT NULL, description VARCHAR(255) DEFAULT NULL, tier INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user_available_booster (id INT AUTO_INCREMENT NOT NULL, user_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', booster_template_id INT NOT NULL, price INT NOT NULL, use_gold TINYINT(1) NOT NULL, INDEX IDX_9C3F0FD3A76ED395 (user_id), INDEX IDX_9C3F0FD3E909B6C7 (booster_template_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_available_booster ADD CONSTRAINT FK_9C3F0FD3A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_available_booster ADD CONSTRAINT FK_9C3F0FD3E909B6C7 FOREIGN KEY (booster_template_id) REFERENCES booster_template (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_booster DROP FOREIGN KEY FK_B77B81A7F85E4930
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_B77B81A7F85E4930 ON user_booster
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_booster CHANGE booster_id booster_template_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_booster ADD CONSTRAINT FK_B77B81A7E909B6C7 FOREIGN KEY (booster_template_id) REFERENCES booster_template (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_B77B81A7E909B6C7 ON user_booster (booster_template_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE user_booster DROP FOREIGN KEY FK_B77B81A7E909B6C7
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_available_booster DROP FOREIGN KEY FK_9C3F0FD3A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_available_booster DROP FOREIGN KEY FK_9C3F0FD3E909B6C7
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE booster_template
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_available_booster
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_B77B81A7E909B6C7 ON user_booster
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_booster CHANGE booster_template_id booster_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_booster ADD CONSTRAINT FK_B77B81A7F85E4930 FOREIGN KEY (booster_id) REFERENCES booster (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_B77B81A7F85E4930 ON user_booster (booster_id)
        SQL);
    }
}
