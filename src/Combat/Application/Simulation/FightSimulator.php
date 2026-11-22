<?php

declare(strict_types=1);

namespace App\Combat\Application\Simulation;

use App\Combat\Domain\Model\Fighter;
use App\Combat\Domain\Service\FightEngine;

final readonly class FightSimulator
{
    public function __construct(private FightEngine $engine)
    {
    }

    public function winRate(Fighter $player, Fighter $npc, int $fights): float
    {
        $wins = 0;
        for ($i = 0; $i < $fights; ++$i) {
            $wins += $this->engine->fight($player, $npc)->playerWon() ? 1 : 0;
        }

        return $fights > 0 ? $wins / $fights : 0.0;
    }
}
