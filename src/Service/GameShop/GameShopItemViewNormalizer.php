<?php

declare(strict_types=1);

namespace App\Service\GameShop;

use App\Entity\ItemStatistics;
use App\Entity\WearableItem;
use App\Enum\WearableItemRarity;

final class GameShopItemViewNormalizer
{
    /**
     * @return array{
     *   id: int,
     *   nameKey: string,
     *   imageKey: ?string,
     *   slotId: string,
     *   price: int,
     *   rarity: string,
     *   stats: list<array{statId: string, value: int}>
     * }|null
     */
    public function normalize(?WearableItem $item): ?array
    {
        if ($item === null) {
            return null;
        }

        $type = $item->getType();
        if ($type === null) {
            return null;
        }

        $stats = $item->getStatistics();
        $shopStats = $stats !== null ? $this->statsLinesFromScalarColumns($stats) : [];

        $nameKey = $item->getNameKey();
        if ($nameKey === null || $nameKey === '') {
            $nameKey = 'items.genericLoot';
        }

        return [
            'id' => (int) $item->getId(),
            'nameKey' => $nameKey,
            'imageKey' => $item->getImageKey(),
            'slotId' => $type->value,
            'price' => (int) $item->getPrice(),
            'rarity' => $this->rarityToClient($item->getRarity()),
            'stats' => $shopStats,
        ];
    }

    /**
     * Display lines for the shop UI — derived only from {@see ItemStatistics} scalar columns.
     *
     * @return list<array{statId: string, value: int}>
     */
    private function statsLinesFromScalarColumns(ItemStatistics $stats): array
    {
        $lines = [];
        if ($stats->getStrongPoints() > 0) {
            $lines[] = ['statId' => 'strength', 'value' => $stats->getStrongPoints()];
        }
        if ($stats->getAgilityPoints() > 0) {
            $lines[] = ['statId' => 'agility', 'value' => $stats->getAgilityPoints()];
        }
        if ($stats->getHealthPoints() > 0) {
            $lines[] = ['statId' => 'health', 'value' => $stats->getHealthPoints()];
        }
        if ($stats->getIntelligencePoints() > 0) {
            $lines[] = ['statId' => 'intelligence', 'value' => $stats->getIntelligencePoints()];
        }
        if ($stats->getCriticalChancePoints() > 0) {
            $lines[] = ['statId' => 'luck', 'value' => $stats->getCriticalChancePoints()];
        }

        return $lines;
    }

    private function rarityToClient(?WearableItemRarity $rarity): string
    {
        if ($rarity === null) {
            return 'common';
        }

        return match ($rarity) {
            WearableItemRarity::COMMON => 'common',
            WearableItemRarity::UNCOMMON => 'uncommon',
            WearableItemRarity::RARE => 'rare',
            WearableItemRarity::EPIC => 'rare',
            WearableItemRarity::LEGENDARY => 'legendary',
        };
    }
}
