<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260531120000_PremiumShopTransaction extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add premium shop transaction history table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE premium_shop_transaction (
            id INT AUTO_INCREMENT NOT NULL,
            user_id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\',
            pack_id VARCHAR(32) NOT NULL,
            diamonds INT NOT NULL,
            price_pln NUMERIC(10, 2) NOT NULL,
            purchased_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX IDX_PREMIUM_SHOP_TX_USER (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE premium_shop_transaction ADD CONSTRAINT FK_PREMIUM_SHOP_TX_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE premium_shop_transaction DROP FOREIGN KEY FK_PREMIUM_SHOP_TX_USER');
        $this->addSql('DROP TABLE premium_shop_transaction');
    }
}
