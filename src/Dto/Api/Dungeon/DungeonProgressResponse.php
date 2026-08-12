<?php

declare(strict_types=1);

namespace App\Dto\Api\Dungeon;

final readonly class DungeonProgressResponse
{
    /**
     * @param array{normal: array<string, int>, hard: array<string, int>} $progress
     */
    public function __construct(
        public array $progress,
        public DungeonPlayerStatsDto $playerStats,
        public ?string $cooldownUntil = null,
        public int $cooldownSecondsRemaining = 0,
    ) {
    }

    /**
     * @return array{
     *     progress: array{normal: array<string, int>, hard: array<string, int>},
     *     playerStats: array{
     *         level: int,
     *         strength: int,
     *         agility: int,
     *         endurance: int,
     *         intelligence: int,
     *         luck: int
     *     },
     *     cooldownUntil: string|null,
     *     cooldownSecondsRemaining: int
     * }
     */
    public function toArray(): array
    {
        return [
            'progress' => $this->progress,
            'playerStats' => $this->playerStats->toArray(),
            'cooldownUntil' => $this->cooldownUntil,
            'cooldownSecondsRemaining' => $this->cooldownSecondsRemaining,
        ];
    }
}
