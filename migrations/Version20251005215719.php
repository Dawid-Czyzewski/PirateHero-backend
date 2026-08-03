<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251005215719 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE user_capacities (id INT AUTO_INCREMENT NOT NULL, user_id CHAR(36) NOT NULL COMMENT '(DC2Type:guid)', energy_points INT NOT NULL, training_points INT NOT NULL, fight_points INT NOT NULL, UNIQUE INDEX UNIQ_DC2B4207A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_capacities ADD CONSTRAINT FK_DC2B4207A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE user_capacities DROP FOREIGN KEY FK_DC2B4207A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE user_capacities
        SQL);
    }
}
