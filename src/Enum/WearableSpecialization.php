<?php

declare(strict_types=1);

namespace App\Enum;

enum WearableSpecialization: string
{
    case Health = 'health';
    case Crit = 'crit';
    case MissionGold = 'mission_gold';

    public static function tryFromClient(string $value): ?self
    {
        return self::tryFrom($value);
    }
}
