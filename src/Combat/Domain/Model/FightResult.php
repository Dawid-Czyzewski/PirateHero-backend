<?php

declare(strict_types=1);

namespace App\Combat\Domain\Model;

final readonly class FightResult
{
    public function __construct(
        public FighterSide $winner,
        public bool $knockout,
        public FightLog $log,
    ) {
    }

    public function playerWon(): bool
    {
        return FighterSide::Player === $this->winner;
    }

    /**
     * @return array{winner: string, knockout: bool, rounds: list<array{number: int, attacker: string, damage: int, playerHp: int, npcHp: int}>}
     */
    public function toArray(): array
    {
        return [
            'winner' => $this->winner->value,
            'knockout' => $this->knockout,
            'rounds' => $this->log->toArray(),
        ];
    }
}
