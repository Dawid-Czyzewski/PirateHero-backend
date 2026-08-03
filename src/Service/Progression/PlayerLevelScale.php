<?php

declare(strict_types=1);

namespace App\Service\Progression;

use App\Domain\Constants\ProgressionConstants;

final class PlayerLevelScale
{
    public static function factor(int $playerLevel): float
    {
        $level = max(1, $playerLevel);

        return 1.0 + ($level - 1) * ProgressionConstants::LEVEL_SCALE_K;
    }
}
