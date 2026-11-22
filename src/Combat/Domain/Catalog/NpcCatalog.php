<?php

declare(strict_types=1);

namespace App\Combat\Domain\Catalog;

use App\Combat\Domain\Exception\NpcNotFound;
use App\Combat\Domain\Model\Fighter;
use App\Combat\Domain\Model\Npc;
use App\Combat\Domain\Model\NpcLook;

final readonly class NpcCatalog
{
    /**
     * @var list<array{id: string, nick: string, level: int, stats: array{int, int, int, int}, look: array{int, int, int, int, int}}>
     */
    private const ROSTER = [
        ['id' => 'kosa', 'nick' => 'Kosa', 'level' => 1, 'stats' => [1, 1, 1, 1], 'look' => [0, 2, 0, 1, 0]],
        ['id' => 'mlody-wilk', 'nick' => 'Młody Wilk', 'level' => 2, 'stats' => [2, 1, 2, 1], 'look' => [1, 5, 1, 0, 2]],
        ['id' => 'cichy', 'nick' => 'Cichy', 'level' => 3, 'stats' => [1, 2, 4, 1], 'look' => [2, 0, 3, 2, 1]],
        ['id' => 'pala', 'nick' => 'Pała', 'level' => 4, 'stats' => [4, 3, 1, 2], 'look' => [3, 1, 4, 3, 3]],
        ['id' => 'rekin', 'nick' => 'Rekin', 'level' => 5, 'stats' => [4, 4, 3, 1], 'look' => [4, 3, 5, 1, 4]],
        ['id' => 'doktor-cios', 'nick' => 'Doktor Cios', 'level' => 6, 'stats' => [4, 4, 5, 1], 'look' => [5, 4, 2, 4, 5]],
        ['id' => 'szrama', 'nick' => 'Szrama', 'level' => 7, 'stats' => [4, 6, 4, 2], 'look' => [6, 6, 6, 2, 6]],
        ['id' => 'krol-parkingu', 'nick' => 'Król Parkingu', 'level' => 8, 'stats' => [5, 5, 5, 3], 'look' => [7, 7, 7, 3, 7]],
    ];

    /**
     * @return list<Npc>
     */
    public function all(): array
    {
        return array_map(self::npcFrom(...), self::ROSTER);
    }

    public function ofId(string $id): Npc
    {
        foreach (self::ROSTER as $entry) {
            if ($entry['id'] === $id) {
                return self::npcFrom($entry);
            }
        }

        throw NpcNotFound::withId($id);
    }

    /**
     * @param array{id: string, nick: string, level: int, stats: array{int, int, int, int}, look: array{int, int, int, int, int}} $entry
     */
    private static function npcFrom(array $entry): Npc
    {
        [$strength, $endurance, $technique, $charisma] = $entry['stats'];
        [$face, $hair, $beard, $skin, $tattoo] = $entry['look'];

        return new Npc(
            $entry['id'],
            new Fighter($entry['nick'], $entry['level'], $strength, $endurance, $technique, $charisma),
            new NpcLook($face, $hair, $beard, $skin, $tattoo),
        );
    }
}
