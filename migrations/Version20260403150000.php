<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260403150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Shop UI: wearable_item.name_key for i18n; wearable types shield, gloves, potions.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE wearable_item ADD name_key VARCHAR(160) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE wearable_item DROP name_key');
    }
}
