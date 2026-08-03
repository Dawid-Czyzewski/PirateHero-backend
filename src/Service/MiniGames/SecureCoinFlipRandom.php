<?php

declare(strict_types=1);

namespace App\Service\MiniGames;

use App\Enum\CoinFlipSide;

final class SecureCoinFlipRandom implements CoinFlipRandomInterface
{
    public function flip(): CoinFlipSide
    {
        return random_int(0, 1) === 0 ? CoinFlipSide::Heads : CoinFlipSide::Tails;
    }
}
