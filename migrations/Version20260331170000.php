<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260331170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename premium currency columns from fame_coins to diamonds.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` CHANGE fame_coins diamonds INT NOT NULL');
        $this->addSql('ALTER TABLE club CHANGE fame_coins diamonds INT NOT NULL');
        $this->addSql('ALTER TABLE club_member CHANGE fame_coins_contributed diamonds_contributed INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` CHANGE diamonds fame_coins INT NOT NULL');
        $this->addSql('ALTER TABLE club CHANGE diamonds fame_coins INT NOT NULL');
        $this->addSql('ALTER TABLE club_member CHANGE diamonds_contributed fame_coins_contributed INT NOT NULL');
    }
}
