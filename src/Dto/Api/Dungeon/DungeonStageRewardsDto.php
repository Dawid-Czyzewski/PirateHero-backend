<?php

declare(strict_types=1);

namespace App\Dto\Api\Dungeon;

final readonly class DungeonStageRewardsDto
{
    public function __construct(
        public int $gold,
        public int $exp,
    ) {
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
