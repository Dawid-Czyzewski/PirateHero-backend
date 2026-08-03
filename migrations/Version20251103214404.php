<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251103214404 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE booster (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, type VARCHAR(255) NOT NULL, effect_amount INT NOT NULL, price INT NOT NULL, use_gold TINYINT(1) NOT NULL, description VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE user_booster (id INT AUTO_INCREMENT NOT NULL, user_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', booster_id INT NOT NULL, expires_at DATETIME NOT NULL, INDEX IDX_B77B81A7A76ED395 (user_id), INDEX IDX_B77B81A7F85E4930 (booster_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_booster ADD CONSTRAINT FK_B77B81A7A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_booster ADD CONSTRAINT FK_B77B81A7F85E4930 FOREIGN KEY (booster_id) REFERENCES booster (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE user_booster DROP FOREIGN KEY FK_B77B81A7A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_booster DROP FOREIGN KEY FK_B77B81A7F85E4930
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE booster
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_booster
        SQL);
    }
}
