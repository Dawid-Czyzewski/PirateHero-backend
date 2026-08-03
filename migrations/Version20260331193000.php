<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260331193000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename user_base_statistics columns to new stat model and add luck_points.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_base_statistics CHANGE health_points endurance_points INT NOT NULL');
        $this->addSql('ALTER TABLE user_base_statistics CHANGE strong_points strength_points INT NOT NULL');
        $this->addSql('ALTER TABLE user_base_statistics CHANGE critical_chance_points intelligence_points INT NOT NULL');
        $this->addSql('ALTER TABLE user_base_statistics ADD luck_points INT NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_base_statistics DROP luck_points');
        $this->addSql('ALTER TABLE user_base_statistics CHANGE endurance_points health_points INT NOT NULL');
        $this->addSql('ALTER TABLE user_base_statistics CHANGE strength_points strong_points INT NOT NULL');
        $this->addSql('ALTER TABLE user_base_statistics CHANGE intelligence_points critical_chance_points INT NOT NULL');
    }
}
