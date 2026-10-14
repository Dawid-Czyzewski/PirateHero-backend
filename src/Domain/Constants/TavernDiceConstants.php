<?php

declare(strict_types=1);

namespace App\Domain\Constants;

final class TavernDiceConstants
{
    public const MIN_STAKE = 10;

    public const MAX_STAKE = 500;

    public const PAYOUT_MULTIPLIER = 1.9;

    public static function payout(int $stake): int
    {
        return (int) floor($stake * self::PAYOUT_MULTIPLIER);
    }
}
