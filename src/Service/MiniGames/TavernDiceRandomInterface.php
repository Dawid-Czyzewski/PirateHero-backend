<?php

declare(strict_types=1);

namespace App\Service\MiniGames;

interface TavernDiceRandomInterface
{
    /** @return int Sum of 2d6 (2..12) */
    public function roll2d6(): int;
}
