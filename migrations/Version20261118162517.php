<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261118162517 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Character experience points, 0 for existing characters';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE characters ADD experience INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE characters DROP experience');
    }
}
