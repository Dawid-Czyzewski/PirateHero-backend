<?php

declare(strict_types=1);

namespace App\Enum;

enum QuestRewardType: string
{
    case EXPERIENCE = 'EXPERIENCE';
    case GOLD = 'GOLD';
    case diamonds = 'diamonds';
    case ITEM = 'ITEM';
}
