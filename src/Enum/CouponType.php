<?php

declare(strict_types=1);

namespace App\Enum;

enum CouponType: string
{
    case MULTI_USE = 'MULTI_USE';
    case SINGLE_USE = 'SINGLE_USE';
}
