<?php

declare(strict_types=1);

namespace App\Enum;

enum FightMoveResult: string
{
    case HIT = 'HIT';
    case CRITICAL_HIT = 'CRITICAL_HIT';
    case DODGE = 'DODGE';
}
