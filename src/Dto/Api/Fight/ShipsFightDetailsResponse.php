<?php

declare(strict_types=1);

namespace App\Dto\Api\Fight;

final readonly class ShipsFightDetailsResponse
{
    /**
     * @param list<ShipsFightMoveDto> $moves
     * @param array{id: int|string, title: string} $attackerShip
     * @param array{id: int|string, title: string} $defenderShip
     * @param list<array{id: int|string, username: string, avatarName: string|null, initialHealth: int}> $attackerMembers
     * @param list<array{id: int|string, username: string, avatarName: string|null, initialHealth: int}> $defenderMembers
     */
    public function __construct(
        public string $result,
        public int $attackerScore,
        public int $defenderScore,
        public int $viewerFameChange,
        public array $moves,
        public array $attackerShip,
        public array $defenderShip,
        public int $attackerInitialHealth,
        public int $defenderInitialHealth,
        public array $attackerMembers,
        public array $defenderMembers,
    ) {
    }

    /**
     * @return array{
     *     result: string,
     *     attackerScore: int,
     *     defenderScore: int,
     *     viewerFameChange: int,
     *     moves: list<array{
     *         moveNumber: int,
     *         player: array{id: int|string, username: string},
     *         target: array{id: int|string, username: string},
     *         result: string,
     *         damage: int,
     *         targetHealthAfter: int,
     *         playerHealthAfter: int|null,
     *         attackerHealth: int|null,
     *         defenderHealth: int|null,
     *         isAttackerSide: bool
     *     }>,
     *     attackerShip: array{id: int|string, title: string},
     *     defenderShip: array{id: int|string, title: string},
     *     attackerInitialHealth: int,
     *     defenderInitialHealth: int,
     *     attackerMembers: list<array{id: int|string, username: string, avatarName: string|null, initialHealth: int}>,
     *     defenderMembers: list<array{id: int|string, username: string, avatarName: string|null, initialHealth: int}>
     * }
     */
    public function toArray(): array
    {
        return [
            'result' => $this->result,
            'attackerScore' => $this->attackerScore,
            'defenderScore' => $this->defenderScore,
            'viewerFameChange' => $this->viewerFameChange,
            'moves' => array_map(static fn (ShipsFightMoveDto $m) => $m->toArray(), $this->moves),
            'attackerShip' => $this->attackerShip,
            'defenderShip' => $this->defenderShip,
            'attackerInitialHealth' => $this->attackerInitialHealth,
            'defenderInitialHealth' => $this->defenderInitialHealth,
            'attackerMembers' => $this->attackerMembers,
            'defenderMembers' => $this->defenderMembers,
        ];
    }
}
