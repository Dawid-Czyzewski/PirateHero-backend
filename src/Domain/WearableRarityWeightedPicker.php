<?php

declare(strict_types=1);

namespace App\Domain;

use App\Domain\Constants\EquipmentConstants;
use App\Enum\WearableItemRarity;


final class WearableRarityWeightedPicker
{
    /**
     * @template T
     * @param list<T> $candidates
     * @param callable(T): WearableItemRarity $rarityOf
     * @return T
     */
    public static function pick(array $candidates, callable $rarityOf): mixed
    {
        if ($candidates === []) {
            throw new \InvalidArgumentException('Cannot pick from an empty candidate list.');
        }

        if (\count($candidates) === 1) {
            return $candidates[0];
        }

        /** @var array<string, list<T>> $byRarity */
        $byRarity = [];
        foreach ($candidates as $candidate) {
            $key = $rarityOf($candidate)->value;
            $byRarity[$key][] = $candidate;
        }

        $weights = EquipmentConstants::RARITY_DROP_WEIGHTS;
        $total = 0;
        $rarityKeys = [];
        foreach ($byRarity as $rarityKey => $_) {
            $w = $weights[$rarityKey] ?? 1;
            if ($w <= 0) {
                continue;
            }
            $rarityKeys[] = $rarityKey;
            $total += $w;
        }

        if ($total <= 0 || $rarityKeys === []) {
            return $candidates[random_int(0, \count($candidates) - 1)];
        }

        $roll = random_int(1, $total);
        $cursor = 0;
        $pickedRarity = $rarityKeys[0];
        foreach ($rarityKeys as $rarityKey) {
            $cursor += $weights[$rarityKey] ?? 1;
            if ($roll <= $cursor) {
                $pickedRarity = $rarityKey;
                break;
            }
        }

        $pool = $byRarity[$pickedRarity];

        return $pool[random_int(0, \count($pool) - 1)];
    }
}
