<?php

declare(strict_types=1);

namespace App\Service\ShopBoosters;

use App\Enum\ShopBoosterCategory;
use App\Enum\ShopBoosterCurrency;

final class ShopBoosterCatalogDefinition
{
    public const DURATION_HOURS = 96;

    public static function translationNameKey(string $publicCode): string
    {
        return 'shopBooster.catalog.'.$publicCode.'.name';
    }

    public static function translationDescriptionKey(string $publicCode): string
    {
        return 'shopBooster.catalog.'.$publicCode.'.description';
    }

    /**
     * @return list<array{
     *     publicCode: string,
     *     category: ShopBoosterCategory,
     *     currency: ShopBoosterCurrency,
     *     price: int,
     *     durationHours: int,
     *     name: string,
     *     description: string,
     *     effect: string,
     *     sortOrder: int
     * }>
     */
    public static function rows(): array
    {
        $h = self::DURATION_HOURS;

        $r = static fn (
            string $code,
            ShopBoosterCategory $cat,
            ShopBoosterCurrency $cur,
            int $price,
            string $effect,
            int $sortOrder,
        ): array => [
            'publicCode' => $code,
            'category' => $cat,
            'currency' => $cur,
            'price' => $price,
            'durationHours' => $h,
            'name' => self::translationNameKey($code),
            'description' => self::translationDescriptionKey($code),
            'effect' => $effect,
            'sortOrder' => $sortOrder,
        ];

        return [
            $r('mis_1', ShopBoosterCategory::Missions, ShopBoosterCurrency::Gold, 400, '+5%', 1),
            $r('mis_2', ShopBoosterCategory::Missions, ShopBoosterCurrency::Gold, 1200, '+15%', 2),
            $r('mis_3', ShopBoosterCategory::Missions, ShopBoosterCurrency::Premium, 5, '+40%', 3),
            $r('trn_1', ShopBoosterCategory::Training, ShopBoosterCurrency::Gold, 400, '+5 pkt treningu', 4),
            $r('trn_2', ShopBoosterCategory::Training, ShopBoosterCurrency::Gold, 1200, '+15 pkt treningu', 5),
            $r('trn_3', ShopBoosterCategory::Training, ShopBoosterCurrency::Premium, 5, '+40 pkt treningu', 6),
            $r('wrk_1', ShopBoosterCategory::Work, ShopBoosterCurrency::Gold, 400, '+5%', 7),
            $r('wrk_2', ShopBoosterCategory::Work, ShopBoosterCurrency::Gold, 1200, '+15%', 8),
            $r('wrk_3', ShopBoosterCategory::Work, ShopBoosterCurrency::Premium, 5, '+40%', 9),
            $r('skl_1', ShopBoosterCategory::Skills, ShopBoosterCurrency::Gold, 400, '+5%', 10),
            $r('skl_2', ShopBoosterCategory::Skills, ShopBoosterCurrency::Gold, 1200, '+15%', 11),
            $r('skl_3', ShopBoosterCategory::Skills, ShopBoosterCurrency::Premium, 5, '+40%', 12),
        ];
    }
}
