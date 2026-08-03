<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251106202014 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add club upgrade fields (skillsUpgrade, workUpgrade, missionsUpgrade)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE club ADD skills_upgrade INT NOT NULL DEFAULT 0, ADD work_upgrade INT NOT NULL DEFAULT 0, ADD missions_upgrade INT NOT NULL DEFAULT 0
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE club DROP skills_upgrade, DROP work_upgrade, DROP missions_upgrade
        SQL);
    }
}
