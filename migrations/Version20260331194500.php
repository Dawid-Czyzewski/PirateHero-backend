<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260331194500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalize user avatar_name values to frontend file keys.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE `user` SET avatar_name = 'avatar1' WHERE LOWER(TRIM(avatar_name)) = 'kapitan'");
        $this->addSql("UPDATE `user` SET avatar_name = 'avatar2' WHERE LOWER(TRIM(avatar_name)) = 'bosman'");
        $this->addSql("UPDATE `user` SET avatar_name = 'avatar3' WHERE LOWER(TRIM(avatar_name)) = 'nawigator'");
        $this->addSql("UPDATE `user` SET avatar_name = 'avatar4' WHERE LOWER(TRIM(avatar_name)) = 'lotrzyk'");
        $this->addSql("UPDATE `user` SET avatar_name = 'avatar5' WHERE LOWER(TRIM(avatar_name)) = 'bukanier'");
        $this->addSql("UPDATE `user` SET avatar_name = 'avatar6' WHERE LOWER(TRIM(avatar_name)) = 'admiral'");
        $this->addSql("UPDATE `user` SET avatar_name = 'avatar7' WHERE LOWER(TRIM(avatar_name)) = 'kapitanka'");
        $this->addSql("UPDATE `user` SET avatar_name = 'avatar8' WHERE LOWER(TRIM(avatar_name)) = 'czarodziejka'");
        $this->addSql("UPDATE `user` SET avatar_name = 'avatar9' WHERE LOWER(TRIM(avatar_name)) = 'zwiadowczyni'");
        $this->addSql("UPDATE `user` SET avatar_name = 'avatar10' WHERE LOWER(TRIM(avatar_name)) = 'wojowniczka'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE `user` SET avatar_name = 'kapitan' WHERE LOWER(TRIM(avatar_name)) = 'avatar1'");
        $this->addSql("UPDATE `user` SET avatar_name = 'bosman' WHERE LOWER(TRIM(avatar_name)) = 'avatar2'");
        $this->addSql("UPDATE `user` SET avatar_name = 'nawigator' WHERE LOWER(TRIM(avatar_name)) = 'avatar3'");
        $this->addSql("UPDATE `user` SET avatar_name = 'lotrzyk' WHERE LOWER(TRIM(avatar_name)) = 'avatar4'");
        $this->addSql("UPDATE `user` SET avatar_name = 'bukanier' WHERE LOWER(TRIM(avatar_name)) = 'avatar5'");
        $this->addSql("UPDATE `user` SET avatar_name = 'admiral' WHERE LOWER(TRIM(avatar_name)) = 'avatar6'");
        $this->addSql("UPDATE `user` SET avatar_name = 'kapitanka' WHERE LOWER(TRIM(avatar_name)) = 'avatar7'");
        $this->addSql("UPDATE `user` SET avatar_name = 'czarodziejka' WHERE LOWER(TRIM(avatar_name)) = 'avatar8'");
        $this->addSql("UPDATE `user` SET avatar_name = 'zwiadowczyni' WHERE LOWER(TRIM(avatar_name)) = 'avatar9'");
        $this->addSql("UPDATE `user` SET avatar_name = 'wojowniczka' WHERE LOWER(TRIM(avatar_name)) = 'avatar10'");
    }
}
