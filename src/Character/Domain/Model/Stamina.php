<?php

declare(strict_types=1);

namespace App\Character\Domain\Model;

use App\Character\Domain\Exception\InsufficientStamina;

final readonly class Stamina extends RegeneratingResource
{
    public const MAX = 100;
    public const SECONDS_PER_POINT = 180;

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
        return 'stamina';
    }

    protected static function insufficient(int $cost, int $available): InsufficientStamina
    {
        return InsufficientStamina::forCost($cost, $available);
    }
}
