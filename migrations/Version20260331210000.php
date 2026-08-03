<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260331210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add luck_points_price to user_skill_points_prices for purchased luck attribute upgrades.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_skill_points_prices ADD luck_points_price INT NOT NULL DEFAULT 5');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_skill_points_prices DROP luck_points_price');
    }
}
