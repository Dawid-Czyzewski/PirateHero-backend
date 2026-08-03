<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260401190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop mission.description (unused; titles are i18n keys)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mission DROP description');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE mission ADD description VARCHAR(255) NOT NULL DEFAULT ''");
    }
}
