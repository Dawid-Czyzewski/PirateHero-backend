<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260617200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add wearable_item_template catalog table (category, image, level range).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE wearable_item_template (
                id INT AUTO_INCREMENT NOT NULL,
                public_code VARCHAR(64) NOT NULL,
                type VARCHAR(255) NOT NULL,
                name_key VARCHAR(160) NOT NULL,
                image_key VARCHAR(64) NOT NULL,
                rarity VARCHAR(255) NOT NULL,
                base_price INT NOT NULL,
                min_level INT NOT NULL DEFAULT 1,
                max_level INT NOT NULL DEFAULT 10,
                strong_points INT NOT NULL DEFAULT 0,
                agility_points INT NOT NULL DEFAULT 0,
                health_points INT NOT NULL DEFAULT 0,
                critical_chance_points INT NOT NULL DEFAULT 0,
                intelligence_points INT NOT NULL DEFAULT 0,
                UNIQUE INDEX UNIQ_WEARABLE_ITEM_TEMPLATE_CODE (public_code),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE wearable_item_template');
    }
}
