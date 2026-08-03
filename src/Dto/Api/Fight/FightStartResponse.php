<?php

declare(strict_types=1);

namespace App\Dto\Api\Fight;

final readonly class FightStartResponse
{
    /**
     * @param list<FightMoveDto> $moves
     * @param array<string, int> $attackerStats
     * @param array<string, int> $defenderStats
     * @param array{id: int|string, username: string, avatarName: string|null, famePoints: int|null} $opponent
     * @param list<array{
     *     id: int|string,
     *     username: string,
     *     avatarName: string|null,
     *     famePoints: int|null,
     *     level: string,
     *     averageSkill: float,
     *     totalStats: array<string, int>
     * }> $opponents
     */
    public function __construct(
        public int $fightId,
        public string $result,
        public int $attackerScore,
        public int $defenderScore,
        public int $famePointsChange,
        public int $duelPointsSpent,
        public int|string $playerId,
        public int|string $opponentId,
        public string $attackerUsername,
        public array $moves,
        public array $attackerStats,
        public array $defenderStats,
        public array $opponent,
        public array $opponents,
    ) {
    }

    /**
     * @return array{
     *     fightId: int,
     *     result: string,
     *     attackerScore: int,
     *     defenderScore: int,
     *     famePointsChange: int,
     *     duelPointsSpent: int,
     *     playerId: int|string,
     *     opponentId: int|string,
     *     attackerUsername: string,
     *     moves: list<array{
     *         moveNumber: int,
     *         player: array{id: int|string, username: string},
     *         result: string,
     *         damage: int,
     *         attackerHealthAfter: int,
     *         defenderHealthAfter: int
     *     }>,
     *     attackerStats: array<string, int>,
     *     defenderStats: array<string, int>,
     *     opponent: array{id: int|string, username: string, avatarName: string|null, famePoints: int|null},
     *     opponents: list<array{
     *         id: int|string,
     *         username: string,
     *         avatarName: string|null,
     *         famePoints: int|null,
     *         level: string,
     *         averageSkill: float,
     *         totalStats: array<string, int>
     *     }>
     * }
     */
    public function toArray(): array
    {
        return [
            'fightId' => $this->fightId,
            'result' => $this->result,
            'attackerScore' => $this->attackerScore,
            'defenderScore' => $this->defenderScore,
            'famePointsChange' => $this->famePointsChange,
            'duelPointsSpent' => $this->duelPointsSpent,
            'playerId' => $this->playerId,
            'opponentId' => $this->opponentId,
            'attackerUsername' => $this->attackerUsername,
            'moves' => array_map(static fn (FightMoveDto $m) => $m->toArray(), $this->moves),
            'attackerStats' => $this->attackerStats,
            'defenderStats' => $this->defenderStats,
            'opponent' => $this->opponent,
            'opponents' => $this->opponents,
        ];
    }
}
