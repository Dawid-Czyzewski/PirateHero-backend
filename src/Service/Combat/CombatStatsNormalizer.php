<?php

declare(strict_types=1);

namespace App\Service\Combat;


final class CombatStatsNormalizer
{
    /**
     * @param array<string, int> $stats
     *
     * @return array{strength: int, agility: int, luck: int, intelligence: int, health: int}
     */
    public static function forCombat(array $stats): array
    {
        $luck = (int) ($stats['luck'] ?? 0);
        $legacyCrit = (int) ($stats['critical'] ?? 0);
        if ($luck === 0 && $legacyCrit > 0) {
            $luck = $legacyCrit;
        }

        return [
            'health' => (int) ($stats['health'] ?? 0),
            'strength' => (int) ($stats['strength'] ?? 0),
            'agility' => (int) ($stats['agility'] ?? 0),
            'luck' => $luck,
            'intelligence' => (int) ($stats['intelligence'] ?? 0),
        ];
    }
}
