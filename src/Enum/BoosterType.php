<?php

declare(strict_types=1);

namespace App\Enum;

enum BoosterType: string
{
    case ENERGY = 'ENERGY';
    case TRAINING_POINTS = 'TRAINING_POINTS';
    case DUEL_POINTS = 'DUEL_POINTS';
    case SKILLS = 'SKILLS';
}
