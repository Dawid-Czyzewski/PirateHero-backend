<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260402120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop work.description (titles use i18n keys; long copy removed)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE work DROP description');
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE work ADD description VARCHAR(255) NOT NULL DEFAULT ''");
    }
}
