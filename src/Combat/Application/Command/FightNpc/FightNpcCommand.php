<?php

declare(strict_types=1);

namespace App\Combat\Application\Command\FightNpc;

final readonly class FightNpcCommand
{
    public function __construct(
        public string $userId,
        public string $npcId,
    ) {
    }
}
