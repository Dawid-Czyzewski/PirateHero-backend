<?php

declare(strict_types=1);

namespace App\Enum;

enum FightResult: string
{
    case ATTACKER_WON = 'ATTACKER_WON';
    case DEFENDER_WON = 'DEFENDER_WON';
}
