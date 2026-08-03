<?php

declare(strict_types=1);

namespace App\Enum;

enum ShopBoosterCategory: string
{
    case Missions = 'missions';
    case Training = 'training';
    case Work = 'work';
    case Skills = 'skills';
}
