<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260517120000_AddPasswordResetToUser extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add password reset token and expiry columns to user table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD password_reset_token VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE `user` ADD password_reset_token_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_USER_PASSWORD_RESET_TOKEN ON `user` (password_reset_token)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_USER_PASSWORD_RESET_TOKEN ON `user`');
        $this->addSql('ALTER TABLE `user` DROP password_reset_token_expires_at');
        $this->addSql('ALTER TABLE `user` DROP password_reset_token');
    }
}
