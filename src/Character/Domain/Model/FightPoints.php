<?php

declare(strict_types=1);

namespace App\Character\Domain\Model;

use App\Character\Domain\Exception\InsufficientFightPoints;

final readonly class FightPoints extends RegeneratingResource
{
    public const MAX = 10;
    public const SECONDS_PER_POINT = 900;

    public static function max(): int
    {
        return self::MAX;
    }

    protected static function secondsPerPoint(): int
    {
        return self::SECONDS_PER_POINT;
    }

    protected static function field(): string
    {
        return 'fightPoints';
    }

    protected static function insufficient(int $cost, int $available): InsufficientFightPoints
    {
        return InsufficientFightPoints::forCost($cost, $available);
    }
}
