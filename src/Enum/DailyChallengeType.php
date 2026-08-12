<?php

declare(strict_types=1);

namespace App\Enum;

enum DailyChallengeType: string
{
    case Missions = 'missions';
    case ArenaWins = 'arena_wins';
    case GoldSpent = 'gold_spent';
}
