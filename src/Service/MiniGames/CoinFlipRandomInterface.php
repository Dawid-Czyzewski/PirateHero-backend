<?php

declare(strict_types=1);

namespace App\Service\MiniGames;

use App\Enum\CoinFlipSide;

interface CoinFlipRandomInterface
{
    public function flip(): CoinFlipSide;
}
