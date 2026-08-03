<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260529120000_AddDailyRewardToUser extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add daily reward progress columns to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD daily_reward_next_day INT NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE `user` ADD last_daily_reward_claim_date DATE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP last_daily_reward_claim_date');
        $this->addSql('ALTER TABLE `user` DROP daily_reward_next_day');
    }
}
