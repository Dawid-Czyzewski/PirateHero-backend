<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251119190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove old mission templates with invalid categories (TRAININGS_COMPLETED, WORKS_COMPLETED, ITEMS_BOUGHT, BOOSTERS_USED)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            DELETE um FROM user_mission um
            INNER JOIN mission_template mt ON um.mission_template_id = mt.id
            WHERE mt.category IN ('TRAININGS_COMPLETED', 'WORKS_COMPLETED', 'ITEMS_BOUGHT', 'BOOSTERS_USED')
        SQL);

        $this->addSql(<<<'SQL'
            DELETE FROM mission_template 
            WHERE category IN ('TRAININGS_COMPLETED', 'WORKS_COMPLETED', 'ITEMS_BOUGHT', 'BOOSTERS_USED')
        SQL);
    }

    public function down(Schema $schema): void
    {
    }
}
