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

    public const TRAINING_COST = 2;
}
