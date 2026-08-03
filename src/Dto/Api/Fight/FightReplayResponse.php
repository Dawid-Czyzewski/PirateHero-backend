<?php

declare(strict_types=1);

namespace App\Dto\Api\Fight;

final readonly class FightReplayResponse
{
    /**
     * @param array{id: int|string, username: string, avatarName: string|null} $attacker
     * @param array{id: int|string, username: string, avatarName: string|null} $defender
     * @param list<FightMoveDto> $moves
     */
    public function __construct(
        public int $fightId,
        public bool $viewerWasAttacker,
        public string $resultForViewer,
        public int $famePointsChangeForViewer,
        public array $attacker,
        public array $defender,
        public int $attackerMaxHp,
        public int $defenderMaxHp,
        public array $moves,
    ) {
    }

    /**
     * @return array{
     *     fightId: int,
     *     viewerWasAttacker: bool,
     *     resultForViewer: string,
     *     famePointsChangeForViewer: int,
     *     attacker: array{id: int|string, username: string, avatarName: string|null},
     *     defender: array{id: int|string, username: string, avatarName: string|null},
     *     attackerMaxHp: int,
     *     defenderMaxHp: int,
     *     moves: list<array{
     *         moveNumber: int,
     *         player: array{id: int|string, username: string},
     *         result: string,
     *         damage: int,
     *         attackerHealthAfter: int,
     *         defenderHealthAfter: int
     *     }>
     * }
     */
    public function toArray(): array
    {
        return [
            'fightId' => $this->fightId,
            'viewerWasAttacker' => $this->viewerWasAttacker,
            'resultForViewer' => $this->resultForViewer,
            'famePointsChangeForViewer' => $this->famePointsChangeForViewer,
            'attacker' => $this->attacker,
            'defender' => $this->defender,
            'attackerMaxHp' => $this->attackerMaxHp,
            'defenderMaxHp' => $this->defenderMaxHp,
            'moves' => array_map(static fn (FightMoveDto $m) => $m->toArray(), $this->moves),
        ];
    }
}
