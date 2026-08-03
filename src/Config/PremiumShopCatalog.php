<?php

declare(strict_types=1);

namespace App\Config;

final class PremiumShopCatalog
{
    /**
     * @return list<array{id: string, diamonds: int, bonusPercent: int, pricePln: float, badge?: string}>
     */
    public static function packs(): array
    {
        return [
            ['id' => 'handful', 'diamonds' => 50, 'bonusPercent' => 0, 'pricePln' => 4.99],
            ['id' => 'pouch', 'diamonds' => 120, 'bonusPercent' => 10, 'pricePln' => 9.99, 'badge' => 'popular'],
            ['id' => 'sack', 'diamonds' => 280, 'bonusPercent' => 15, 'pricePln' => 19.99],
            ['id' => 'chest', 'diamonds' => 600, 'bonusPercent' => 20, 'pricePln' => 39.99, 'badge' => 'bestValue'],
            ['id' => 'vault', 'diamonds' => 1300, 'bonusPercent' => 25, 'pricePln' => 79.99],
            ['id' => 'treasure', 'diamonds' => 2800, 'bonusPercent' => 30, 'pricePln' => 149.99],
        ];
    }

    /**
     * @return list<array{id: string, diamonds: int, bonusPercent: int, pricePln: float, totalDiamonds: int, badge?: string}>
     */
    public static function catalogPacks(): array
    {
        return array_map(static function (array $pack): array {
            $entry = [
                'id' => $pack['id'],
                'diamonds' => $pack['diamonds'],
                'bonusPercent' => $pack['bonusPercent'],
                'pricePln' => $pack['pricePln'],
                'totalDiamonds' => self::totalDiamonds($pack),
            ];
            if (isset($pack['badge'])) {
                $entry['badge'] = $pack['badge'];
            }

            return $entry;
        }, self::packs());
    }

    /**
     * @return array{id: string, diamonds: int, bonusPercent: int, pricePln: float}|null
     */
    public static function findPack(string $packId): ?array
    {
        foreach (self::packs() as $pack) {
            if ($pack['id'] === $packId) {
                return $pack;
            }
        }

        return null;
    }

    /**
     * @param array{diamonds: int, bonusPercent: int} $pack
     */
    public static function totalDiamonds(array $pack): int
    {
        return (int) round($pack['diamonds'] * (1 + $pack['bonusPercent'] / 100));
    }
}
