<?php

declare(strict_types=1);

namespace App\Dto\Api\Dungeon;

final readonly class DungeonPlayerStatsDto
{
    public function __construct(
        public int $level,
        public int $strength,
        public int $agility,
        public int $endurance,
        public int $intelligence,
        public int $luck,
    ) {
    }

    /**
     * @return array{
     *     level: int,
     *     strength: int,
     *     agility: int,
     *     endurance: int,
     *     intelligence: int,
     *     luck: int
     * }
     */
    public function toArray(): array
    {
        return [
            'level' => $this->level,
            'strength' => $this->strength,
            'agility' => $this->agility,
            'endurance' => $this->endurance,
            'intelligence' => $this->intelligence,
            'luck' => $this->luck,
        ];
    }
}
