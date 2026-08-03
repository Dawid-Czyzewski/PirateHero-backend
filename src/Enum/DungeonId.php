<?php

declare(strict_types=1);

namespace App\Enum;

enum DungeonId: string
{
    case Krypta = 'krypta';
    case Kraken = 'kraken';
    case Forteca = 'forteca';
    case Wulkan = 'wulkan';
    case Palac = 'palac';

    public static function tryFromString(string $id): ?self
    {
        return self::tryFrom(strtolower(trim($id)));
    }
}
