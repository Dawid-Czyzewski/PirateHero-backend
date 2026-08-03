<?php

declare(strict_types=1);

namespace App\Enum;

enum CoinFlipSide: string
{
    case Heads = 'heads';
    case Tails = 'tails';
}
