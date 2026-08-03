<?php

declare(strict_types=1);

namespace App\Dungeon;


final readonly class DungeonStageReward
{
    public function __construct(
        public int $gold,
        public int $exp,
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->gold === 0 && $this->exp === 0;
    }

    /**
     * @return array{gold: int, exp: int}
     */
    public function toArray(): array
    {
        return [
            'gold' => $this->gold,
            'exp' => $this->exp,
        ];
    }
}
