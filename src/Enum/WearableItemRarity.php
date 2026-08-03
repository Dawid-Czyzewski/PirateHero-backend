<?php

declare(strict_types=1);

namespace App\Enum;

enum WearableItemRarity: string
{
    case COMMON = 'COMMON';
    case UNCOMMON = 'UNCOMMON';
    case RARE = 'RARE';
    case EPIC = 'EPIC';
    case LEGENDARY = 'LEGENDARY';
}
