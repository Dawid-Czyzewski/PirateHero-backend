<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261124180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Character fight points with regeneration timestamp, existing characters start full';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE characters ADD fight_points INT DEFAULT NULL');
        $this->addSql('ALTER TABLE characters ADD fight_points_updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('UPDATE characters SET fight_points = 10, fight_points_updated_at = NOW()');
        $this->addSql('ALTER TABLE characters ALTER fight_points SET NOT NULL');
        $this->addSql('ALTER TABLE characters ALTER fight_points_updated_at SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE characters DROP fight_points_updated_at');
        $this->addSql('ALTER TABLE characters DROP fight_points');
    }
}
