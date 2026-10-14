<?php

declare(strict_types=1);

namespace App\Service\MiniGames;

final class SecureTavernDiceRandom implements TavernDiceRandomInterface
{
    public function roll2d6(): int
    {
        return random_int(1, 6) + random_int(1, 6);
    }
}
