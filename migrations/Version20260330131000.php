<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260330131000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add unique username and non-negative checks for user economy fields.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_USERNAME ON `user` (username)');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT chk_user_gold_non_negative CHECK (gold >= 0)');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT chk_user_fame_coins_non_negative CHECK (fame_coins >= 0)');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT chk_user_duel_points_non_negative CHECK (duel_points >= 0)');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT chk_user_energy_points_non_negative CHECK (energy_points >= 0)');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT chk_user_training_points_non_negative CHECK (training_points >= 0)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP CONSTRAINT chk_user_training_points_non_negative');
        $this->addSql('ALTER TABLE `user` DROP CONSTRAINT chk_user_energy_points_non_negative');
        $this->addSql('ALTER TABLE `user` DROP CONSTRAINT chk_user_duel_points_non_negative');
        $this->addSql('ALTER TABLE `user` DROP CONSTRAINT chk_user_fame_coins_non_negative');
        $this->addSql('ALTER TABLE `user` DROP CONSTRAINT chk_user_gold_non_negative');
        $this->addSql('DROP INDEX UNIQ_IDENTIFIER_USERNAME ON `user`');
    }
}
