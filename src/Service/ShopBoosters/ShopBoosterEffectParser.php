<?php

declare(strict_types=1);

namespace App\Service\ShopBoosters;

final class ShopBoosterEffectParser
{
    public function parseTrainingFlatBonus(string $effect): int
    {
        if (preg_match('/\+(\d+)\s*pkt treningu/', $effect, $m) === 1) {
            return max(0, (int) $m[1]);
        }

        return 0;
    }

    public function parseFirstPercentFraction(string $effect): float
    {
        if (preg_match('/\+(\d+)%/', $effect, $m) === 1) {
            return max(0, (int) $m[1]) / 100.0;
        }

        return 0.0;
    }
}
