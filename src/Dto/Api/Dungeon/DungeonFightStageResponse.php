<?php

declare(strict_types=1);

namespace App\Dto\Api\Dungeon;

final readonly class DungeonFightStageResponse
{
    /**
     * @param list<DungeonBattleLogEntryDto> $logs
     * @param array{normal: array<string, int>, hard: array<string, int>} $progress
     * @param array{gold: int, diamonds: int, item: array<string, mixed>|null}|null $completionReward
     * @param array<string, mixed>|null $rewardItem
     * @param array{
     *     gold: int,
     *     diamonds: int,
     *     experiencePoints: int,
     *     freeSkillPointsAvailable: int,
     *     level: array{name: string, expToNextLevel: int},
     *     storage?: array{id: int|string, slots: list<array<string, mixed>>}
     * }|null $updatedUser
     */
    public function __construct(
        public bool $won,
        public array $logs,
        public int $playerMaxHp,
        public int $opponentMaxHp,
        public int $fameEarned,
        public int $famePointsChange,
        public array $progress,
        public DungeonOpponentDto $opponent,
        public DungeonStageRewardsDto $rewards,
        public ?array $completionReward,
        public bool $dungeonCompleted,
        public ?array $rewardItem,
        public ?array $updatedUser,
        public ?string $cooldownUntil = null,
        public int $cooldownSecondsRemaining = 0,
    ) {
    }

    /**
     * @return array{
     *     won: bool,
     *     logs: list<array{attackerIsPlayer: bool, damage: int, critical: bool}>,
     *     playerMaxHp: int,
     *     opponentMaxHp: int,
     *     fameEarned: int,
     *     famePointsChange: int,
     *     progress: array{normal: array<string, int>, hard: array<string, int>},
     *     opponent: array{
     *         id: string,
     *         name: string,
     *         enemyNameKey: string,
     *         avatarId: string,
     *         level: int,
     *         famePoints: int,
     *         strength: int,
     *         agility: int,
     *         endurance: int,
     *         intelligence: int,
     *         luck: int
     *     },
     *     rewards: array{gold: int, exp: int},
     *     completionReward: array{gold: int, diamonds: int, item: array<string, mixed>|null}|null,
     *     dungeonCompleted: bool,
     *     rewardItem: array<string, mixed>|null,
     *     updatedUser: array{
     *         gold: int,
     *         diamonds: int,
     *         experiencePoints: int,
     *         freeSkillPointsAvailable: int,
     *         level: array{name: string, expToNextLevel: int},
     *         storage?: array{id: int|string, slots: list<array<string, mixed>>}
     *     }|null,
     *     cooldownUntil: string|null,
     *     cooldownSecondsRemaining: int
     * }
     */
    public function toArray(): array
    {
        return [
            'won' => $this->won,
            'logs' => array_map(static fn (DungeonBattleLogEntryDto $e) => $e->toArray(), $this->logs),
            'playerMaxHp' => $this->playerMaxHp,
            'opponentMaxHp' => $this->opponentMaxHp,
            'fameEarned' => $this->fameEarned,
            'famePointsChange' => $this->famePointsChange,
            'progress' => $this->progress,
            'opponent' => $this->opponent->toArray(),
            'rewards' => $this->rewards->toArray(),
            'completionReward' => $this->completionReward,
            'dungeonCompleted' => $this->dungeonCompleted,
            'rewardItem' => $this->rewardItem,
            'updatedUser' => $this->updatedUser,
            'cooldownUntil' => $this->cooldownUntil,
            'cooldownSecondsRemaining' => $this->cooldownSecondsRemaining,
        ];
    }
}
