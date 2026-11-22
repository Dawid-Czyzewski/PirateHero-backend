<?php

declare(strict_types=1);

namespace App\Combat\Domain\Model;

final readonly class Round
{
    public function __construct(
        public int $number,
        public FighterSide $attacker,
        public int $damage,
        public int $playerHp,
        public int $npcHp,
    ) {
    }

    /**
     * @return array{number: int, attacker: string, damage: int, playerHp: int, npcHp: int}
     */
    public function toArray(): array
    {
        return [
            'number' => $this->number,
            'attacker' => $this->attacker->value,
            'damage' => $this->damage,
            'playerHp' => $this->playerHp,
            'npcHp' => $this->npcHp,
        ];
    }
}
