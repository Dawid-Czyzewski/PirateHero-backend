<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260331211000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename price columns to endurance/intelligence; migrate training stat_type enum strings.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE training SET stat_type = 'INTELLIGENCE' WHERE stat_type = 'CRITICAL_CHANCE'");
        $this->addSql("UPDATE training SET stat_type = 'ENDURANCE' WHERE stat_type = 'HEALTH'");

        $conn = $this->connection;
        $hasHealth = (int) $conn->fetchOne(
            "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = 'user_skill_points_prices' AND COLUMN_NAME = 'health_points_price'"
        );
        if ($hasHealth > 0) {
            $this->addSql('ALTER TABLE user_skill_points_prices CHANGE health_points_price endurance_points_price INT NOT NULL');
        }

        $hasCrit = (int) $conn->fetchOne(
            "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = 'user_skill_points_prices' AND COLUMN_NAME = 'critical_chance_points_price'"
        );
        if ($hasCrit > 0) {
            $this->addSql(
                'ALTER TABLE user_skill_points_prices CHANGE critical_chance_points_price intelligence_points_price INT NOT NULL'
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE training SET stat_type = 'CRITICAL_CHANCE' WHERE stat_type = 'INTELLIGENCE'");
        $this->addSql("UPDATE training SET stat_type = 'HEALTH' WHERE stat_type = 'ENDURANCE'");

        $conn = $this->connection;
        $hasEnd = (int) $conn->fetchOne(
            "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = 'user_skill_points_prices' AND COLUMN_NAME = 'endurance_points_price'"
        );
        if ($hasEnd > 0) {
            $this->addSql('ALTER TABLE user_skill_points_prices CHANGE endurance_points_price health_points_price INT NOT NULL');
        }

        $hasInt = (int) $conn->fetchOne(
            "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()
             AND TABLE_NAME = 'user_skill_points_prices' AND COLUMN_NAME = 'intelligence_points_price'"
        );
        if ($hasInt > 0) {
            $this->addSql(
                'ALTER TABLE user_skill_points_prices CHANGE intelligence_points_price critical_chance_points_price INT NOT NULL'
            );
        }
    }
}
