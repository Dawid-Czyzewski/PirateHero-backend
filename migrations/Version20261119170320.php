<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261119170320 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Character stamina with regeneration timestamp, existing characters keep their points';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE characters RENAME COLUMN energy TO stamina');
        $this->addSql('ALTER TABLE characters ADD stamina_updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('UPDATE characters SET stamina_updated_at = NOW()');
        $this->addSql('ALTER TABLE characters ALTER stamina_updated_at SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE characters DROP stamina_updated_at');
        $this->addSql('ALTER TABLE characters RENAME COLUMN stamina TO energy');
    }
}
