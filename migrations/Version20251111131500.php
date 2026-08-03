<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251111131500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalize club_member.role values to match ClubRole enum cases.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE club_member
            SET role = 'OWNER'
            WHERE role LIKE 'OWNER%'
        SQL);

        $this->addSql(<<<'SQL'
            UPDATE club_member
            SET role = 'MANAGER'
            WHERE role IN ('OFFICER', 'ELDER', 'MANAGER')
               OR role LIKE 'MANAGER%'
        SQL);

        $this->addSql(<<<'SQL'
            UPDATE club_member
            SET role = 'MEMBER'
            WHERE role NOT IN ('OWNER', 'MANAGER', 'MEMBER')
        SQL);
    }

    public function down(Schema $schema): void
    {
    }
}
