<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260823180000_WearableItemUpgradeLevel extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add upgrade_level to wearable_item for captain workshop';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE wearable_item ADD upgrade_level INT NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE wearable_item DROP upgrade_level');
    }
}
