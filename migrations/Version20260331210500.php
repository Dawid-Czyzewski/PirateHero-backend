<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260331210500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure user_skill_points_prices.luck_points_price exists.';
    }

    public function up(Schema $schema): void
    {
        $conn = $this->connection;
        $exists = (int) $conn->fetchOne(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'user_skill_points_prices'
               AND COLUMN_NAME = 'luck_points_price'"
        );
        if ($exists === 0) {
            $this->addSql('ALTER TABLE user_skill_points_prices ADD luck_points_price INT NOT NULL DEFAULT 5');
        }
    }

    public function down(Schema $schema): void
    {
    }
}
