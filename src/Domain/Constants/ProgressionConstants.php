<?php

declare(strict_types=1);

namespace App\Domain\Constants;


final class ProgressionConstants
{
    public const LEVEL_SCALE_K = 0.025;

    public const MAX_LEVEL = 100;

    public const ATTRIBUTE_POINTS_PER_LEVEL_UP = 5;

    public const TRAINING_DURATION_SECONDS = 600;

    public const TRAINING_SKILL_POINTS_REWARD = 2;

    public const TRAINING_SKILL_REWARD_LEVEL_STEP = 4;

    public const TRAINING_COST = 2;

    public static function trainingSkillPointsRewardForLevel(int $level): int
    {
        $level = max(1, $level);

        return self::TRAINING_SKILL_POINTS_REWARD + intdiv($level - 1, self::TRAINING_SKILL_REWARD_LEVEL_STEP);
    }
}
