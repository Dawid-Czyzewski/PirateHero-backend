<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260530120000_UserDailyRewardTable extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move daily reward progress from user table to user_daily_reward';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_daily_reward (
            id INT AUTO_INCREMENT NOT NULL,
            user_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
            next_day INT NOT NULL DEFAULT 1,
            last_claim_date DATE DEFAULT NULL,
            UNIQUE INDEX UNIQ_USER_DAILY_REWARD_USER (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_daily_reward ADD CONSTRAINT FK_USER_DAILY_REWARD_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');

        $this->addSql('INSERT INTO user_daily_reward (user_id, next_day, last_claim_date)
            SELECT id, daily_reward_next_day, last_daily_reward_claim_date FROM `user`');

        $this->addSql('ALTER TABLE `user` DROP last_daily_reward_claim_date');
        $this->addSql('ALTER TABLE `user` DROP daily_reward_next_day');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD daily_reward_next_day INT NOT NULL DEFAULT 1');
        $this->addSql('ALTER TABLE `user` ADD last_daily_reward_claim_date DATE DEFAULT NULL');

        $this->addSql('UPDATE `user` u
            INNER JOIN user_daily_reward r ON r.user_id = u.id
            SET u.daily_reward_next_day = r.next_day,
                u.last_daily_reward_claim_date = r.last_claim_date');

        $this->addSql('ALTER TABLE user_daily_reward DROP FOREIGN KEY FK_USER_DAILY_REWARD_USER');
        $this->addSql('DROP TABLE user_daily_reward');
    }
}
