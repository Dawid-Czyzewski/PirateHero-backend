<?php

declare(strict_types=1);

namespace App\Dto\Api\Dungeon;

final readonly class DungeonProgressResponse
{
    /**
     * @param array<string, int> $progress
     */
    public function __construct(
        public array $progress,
        public DungeonPlayerStatsDto $playerStats,
    ) {
    }

    /**
     * @return array{
     *     progress: array<string, int>,
     *     playerStats: array{
     *         level: int,
     *         strength: int,
     *         agility: int,
     *         endurance: int,
     *         intelligence: int,
     *         luck: int
     *     }
     * }
     */
    public function toArray(): array
    {
        return [
            'progress' => $this->progress,
            'playerStats' => $this->playerStats->toArray(),
        ];
    }
}
