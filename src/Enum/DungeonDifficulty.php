<?php

declare(strict_types=1);

namespace App\Enum;

enum DungeonDifficulty: string
{
    case Normal = 'normal';
    case Hard = 'hard';

    public static function tryFromString(?string $value): ?self
    {
        if ($value === null || $value === '') {
            return self::Normal;
        }

        return self::tryFrom($value);
    }
}
