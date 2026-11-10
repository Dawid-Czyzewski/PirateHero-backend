<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261104201412 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Characters table, one character per user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE characters (user_id UUID NOT NULL, id UUID NOT NULL, level SMALLINT NOT NULL, strength INT NOT NULL, endurance INT NOT NULL, technique INT NOT NULL, charisma INT NOT NULL, cebuliony INT NOT NULL, zlote_zeby INT NOT NULL, kondycha INT NOT NULL, zasiegi INT NOT NULL, medale INT NOT NULL, face SMALLINT NOT NULL, hair SMALLINT NOT NULL, beard SMALLINT NOT NULL, skin SMALLINT NOT NULL, tattoo SMALLINT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3A29410EA76ED395 ON characters (user_id)');
        $this->addSql('ALTER TABLE characters ADD CONSTRAINT fk_characters_user_id FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE characters DROP CONSTRAINT fk_characters_user_id');
        $this->addSql('DROP TABLE characters');
    }
}
