<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;


final class Version20261014130000_Companion extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Companion templates, user_companion unlocks, and user.equipped_companion_id';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE companion_template (
            id INT AUTO_INCREMENT NOT NULL,
            code VARCHAR(64) NOT NULL,
            name_key VARCHAR(128) NOT NULL,
            description_key VARCHAR(128) NOT NULL,
            image_key VARCHAR(128) NOT NULL,
            sort_order INT NOT NULL,
            UNIQUE INDEX UNIQ_COMPANION_TEMPLATE_CODE (code),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE user_companion (
            id INT AUTO_INCREMENT NOT NULL,
            user_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
            companion_template_id INT NOT NULL,
            unlocked_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            level INT NOT NULL,
            last_fed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            last_ability_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_USER_COMPANION_USER (user_id),
            INDEX IDX_USER_COMPANION_TEMPLATE (companion_template_id),
            UNIQUE INDEX UNIQ_USER_COMPANION_TEMPLATE (user_id, companion_template_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE user_companion ADD CONSTRAINT FK_USER_COMPANION_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_companion ADD CONSTRAINT FK_USER_COMPANION_TEMPLATE FOREIGN KEY (companion_template_id) REFERENCES companion_template (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE `user` ADD equipped_companion_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_USER_EQUIPPED_COMPANION FOREIGN KEY (equipped_companion_id) REFERENCES companion_template (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_USER_EQUIPPED_COMPANION ON `user` (equipped_companion_id)');

        $this->addSql("INSERT INTO companion_template (code, name_key, description_key, image_key, sort_order) VALUES
            ('parrot', 'companions.parrot.name', 'companions.parrot.description', 'companions.parrot.image', 1)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_USER_EQUIPPED_COMPANION');
        $this->addSql('DROP INDEX IDX_USER_EQUIPPED_COMPANION ON `user`');
        $this->addSql('ALTER TABLE `user` DROP equipped_companion_id');
        $this->addSql('ALTER TABLE user_companion DROP FOREIGN KEY FK_USER_COMPANION_USER');
        $this->addSql('ALTER TABLE user_companion DROP FOREIGN KEY FK_USER_COMPANION_TEMPLATE');
        $this->addSql('DROP TABLE user_companion');
        $this->addSql('DROP TABLE companion_template');
    }
}
