<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260617210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop price and stat columns from wearable_item_template (rolled at spawn time).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE wearable_item_template DROP base_price');
        $this->addSql('ALTER TABLE wearable_item_template DROP strong_points');
        $this->addSql('ALTER TABLE wearable_item_template DROP agility_points');
        $this->addSql('ALTER TABLE wearable_item_template DROP health_points');
        $this->addSql('ALTER TABLE wearable_item_template DROP critical_chance_points');
        $this->addSql('ALTER TABLE wearable_item_template DROP intelligence_points');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE wearable_item_template ADD base_price INT NOT NULL');
        $this->addSql('ALTER TABLE wearable_item_template ADD strong_points INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE wearable_item_template ADD agility_points INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE wearable_item_template ADD health_points INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE wearable_item_template ADD critical_chance_points INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE wearable_item_template ADD intelligence_points INT NOT NULL DEFAULT 0');
    }
}
