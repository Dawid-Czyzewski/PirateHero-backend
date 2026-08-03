<?php

declare(strict_types=1);

namespace App\Service\GameShop;

use App\Domain\WearableRarityWeightedPicker;
use App\Enum\WearableItemRarity;
use App\Enum\WearableItemType;
use App\Service\Progression\PlayerLevelScale;

final class GameShopOfferRoller
{
    private const LEVEL_CLAMP_MAX = 100;
    private const STAT_JITTER_MIN = 92;
    private const STAT_JITTER_MAX = 108;
    private const PRICE_JITTER_MIN = 95;
    private const PRICE_JITTER_MAX = 105;

    /** @var list<string> */
    private const STAT_POOL = ['strength', 'agility', 'health', 'defense', 'speed', 'luck', 'intelligence'];

    /** @var array<string, array{0: int, 1: int}> */
    private const PRICE_RANGES = [
        'COMMON' => [45, 75],
        'UNCOMMON' => [70, 95],
        'RARE' => [100, 135],
        'EPIC' => [130, 170],
        'LEGENDARY' => [165, 240],
    ];

    /** @var array<string, array{0: int, 1: int}> */
    private const STAT_LINE_COUNTS = [
        'COMMON' => [1, 2],
        'UNCOMMON' => [1, 2],
        'RARE' => [2, 3],
        'EPIC' => [2, 4],
        'LEGENDARY' => [3, 4],
    ];

    /** @var array<string, array{0: int, 1: int}> */
    private const STAT_VALUE_RANGES = [
        'COMMON' => [4, 12],
        'UNCOMMON' => [6, 16],
        'RARE' => [8, 20],
        'EPIC' => [10, 24],
        'LEGENDARY' => [12, 28],
    ];

    public function __construct(
        private readonly WearableVariantResolver $variantResolver,
    ) {
    }

    /**
     * @return array{
     *   nameKey: string,
     *   imageKey: string,
     *   price: int,
     *   rarity: WearableItemRarity,
     *   shopStats: list<array{statId: string, value: int}>
     * }
     */
    public function roll(WearableItemType $type, int $playerLevel): array
    {
        $resolved = $this->variantResolver->resolve($type, $playerLevel, 'shop');
        $variants = $resolved['variants'];
        if ($variants === []) {
            throw new \LogicException('No shop variants for type '.$type->value);
        }

        $base = WearableRarityWeightedPicker::pick(
            $variants,
            static fn (array $v) => $v['rarity']
        );
        $level = max(1, min(self::LEVEL_CLAMP_MAX, $playerLevel));

        $statFactor = PlayerLevelScale::factor($level);
        $statJitter = random_int(self::STAT_JITTER_MIN, self::STAT_JITTER_MAX) / 100.0;

        $scaledStats = $this->rollRandomStatLines($base['rarity'], $statFactor, $statJitter);
        $price = $this->rollPrice($base['rarity'], $level);

        return [
            'nameKey' => $base['nameKey'],
            'imageKey' => $base['imageKey'],
            'price' => $price,
            'rarity' => $base['rarity'],
            'shopStats' => $scaledStats,
        ];
    }

    private function rollPrice(WearableItemRarity $rarity, int $level): int
    {
        $priceFactor = PlayerLevelScale::factor($level);
        $priceJitter = random_int(self::PRICE_JITTER_MIN, self::PRICE_JITTER_MAX) / 100.0;
        [$min, $max] = self::PRICE_RANGES[$rarity->value];
        $base = random_int($min, $max);

        return (int) max(1, round($base * $priceFactor * $priceJitter));
    }

    /**
     * @return list<array{statId: string, value: int}>
     */
    private function rollRandomStatLines(WearableItemRarity $rarity, float $statFactor, float $statJitter): array
    {
        $pool = self::STAT_POOL;
        for ($i = \count($pool) - 1; $i > 0; --$i) {
            $j = random_int(0, $i);
            [$pool[$i], $pool[$j]] = [$pool[$j], $pool[$i]];
        }

        [$cmin, $cmax] = self::STAT_LINE_COUNTS[$rarity->value];
        $lineCount = min(random_int($cmin, $cmax), \count($pool));

        [$vmin, $vmax] = self::STAT_VALUE_RANGES[$rarity->value];

        $lines = [];
        for ($i = 0; $i < $lineCount; ++$i) {
            $sid = $pool[$i];
            $baseVal = random_int($vmin, $vmax);
            $v = (int) max(1, round($baseVal * $statFactor * $statJitter));
            $lines[] = ['statId' => $sid, 'value' => $v];
        }

        return $lines;
    }
}
