<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260403160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop wearable_item.level (shop items no longer use item level).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE wearable_item DROP COLUMN level');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE wearable_item ADD level INT NOT NULL DEFAULT 1');
    }
}
