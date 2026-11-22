<?php

declare(strict_types=1);

namespace App\Combat\Domain\Model;

enum FighterSide: string
{
    case Player = 'player';
    case Npc = 'npc';

    public function opponent(): self
    {
        return self::Player === $this ? self::Npc : self::Player;
    }
}
