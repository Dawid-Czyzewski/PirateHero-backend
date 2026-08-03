<?php

declare(strict_types=1);

namespace App\Enum;

enum CouponRewardType: string
{
    case GOLD = 'GOLD';
    case diamonds = 'diamonds';
    case BOOSTER = 'BOOSTER';
    case ITEM = 'ITEM';
}
