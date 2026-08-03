<?php

declare(strict_types=1);

namespace App\Dto\Api\Fight;

final readonly class FightMoveDto
{
    /**
     * @param array{id: int|string, username: string} $player
     */
    public function __construct(
        public int $moveNumber,
        public array $player,
        public string $result,
        public int $damage,
        public int $attackerHealthAfter,
        public int $defenderHealthAfter,
    ) {
    }

    /**
     * @return array{
     *     moveNumber: int,
     *     player: array{id: int|string, username: string},
     *     result: string,
     *     damage: int,
     *     attackerHealthAfter: int,
     *     defenderHealthAfter: int
     * }
     */
    public function toArray(): array
    {
        return [
            'moveNumber' => $this->moveNumber,
            'player' => $this->player,
            'result' => $this->result,
            'damage' => $this->damage,
            'attackerHealthAfter' => $this->attackerHealthAfter,
            'defenderHealthAfter' => $this->defenderHealthAfter,
        ];
    }
}
