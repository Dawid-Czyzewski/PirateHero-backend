<?php

declare(strict_types=1);

namespace App\Enum;

enum RefillType: string
{
    case ENERGY = 'ENERGY';
    case TRAINING = 'TRAINING';
    case FIGHT = 'FIGHT';
}
