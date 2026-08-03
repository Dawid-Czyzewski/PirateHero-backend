<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Drops item_statistics.character_stats; rolled shop lines live in shop_stats JSON only.
 *
 * DDL must use {@see Connection::executeStatement} so it runs before data UPDATEs;
 * {@see AbstractMigration::addSql} is deferred until after {@see up()} returns.
 */
final class Version20260407220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace item_statistics.character_stats with shop_stats (shop roll lines only).';
    }

    public function up(Schema $schema): void
    {
        $this->connection->executeStatement('ALTER TABLE item_statistics ADD shop_stats JSON DEFAULT NULL');

        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, character_stats FROM item_statistics WHERE character_stats IS NOT NULL'
        );
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $cs = $row['character_stats'];
            if ($cs === null || $cs === '') {
                continue;
            }
            if (is_string($cs)) {
                $decoded = json_decode($cs, true);
                if (!is_array($decoded)) {
                    continue;
                }
            } elseif (is_array($cs)) {
                $decoded = $cs;
            } else {
                continue;
            }
            if (!isset($decoded['shopStats']) || !is_array($decoded['shopStats']) || $decoded['shopStats'] === []) {
                continue;
            }
            $encoded = json_encode($decoded['shopStats'], \JSON_THROW_ON_ERROR);
            $this->connection->executeStatement(
                'UPDATE item_statistics SET shop_stats = ? WHERE id = ?',
                [$encoded, $id]
            );
        }

        $this->connection->executeStatement('ALTER TABLE item_statistics DROP character_stats');
    }

    public function down(Schema $schema): void
    {
        $this->connection->executeStatement('ALTER TABLE item_statistics ADD character_stats JSON DEFAULT NULL');

        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, shop_stats FROM item_statistics WHERE shop_stats IS NOT NULL'
        );
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $ss = $row['shop_stats'];
            if ($ss === null || $ss === '') {
                continue;
            }
            if (is_string($ss)) {
                $lines = json_decode($ss, true);
                if (!is_array($lines)) {
                    continue;
                }
            } elseif (is_array($ss)) {
                $lines = $ss;
            } else {
                continue;
            }
            $wrapped = json_encode(['shopStats' => $lines], \JSON_THROW_ON_ERROR);
            $this->connection->executeStatement(
                'UPDATE item_statistics SET character_stats = ? WHERE id = ?',
                [$wrapped, $id]
            );
        }

        $this->connection->executeStatement('ALTER TABLE item_statistics DROP shop_stats');
    }
}
