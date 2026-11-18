<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261118170000 extends AbstractMigration
{
    private const COLUMNS = [
        'cebuliony' => 'onions',
        'zlote_zeby' => 'gold_teeth',
        'kondycha' => 'energy',
        'zasiegi' => 'reach',
        'medale' => 'medals',
    ];

    public function getDescription(): string
    {
        return 'English names for character wallet columns, balances kept';
    }

    public function up(Schema $schema): void
    {
        foreach (self::COLUMNS as $from => $to) {
            $this->addSql(\sprintf('ALTER TABLE characters RENAME COLUMN %s TO %s', $from, $to));
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::COLUMNS as $from => $to) {
            $this->addSql(\sprintf('ALTER TABLE characters RENAME COLUMN %s TO %s', $to, $from));
        }
    }
}
