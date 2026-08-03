<?php

declare(strict_types=1);

namespace App\Dto\Api\Fight;

final readonly class ShipsFightMoveDto
{
    /**
     * @param array{id: int|string, username: string} $player
     * @param array{id: int|string, username: string} $target
     */
    public function __construct(
        public int $moveNumber,
        public array $player,
        public array $target,
        public string $result,
        public int $damage,
        public int $targetHealthAfter,
        public ?int $playerHealthAfter,
        public ?int $attackerHealth,
        public ?int $defenderHealth,
        public bool $isAttackerSide,
    ) {
    }

    /**
     * @return array{
     *     moveNumber: int,
     *     player: array{id: int|string, username: string},
     *     target: array{id: int|string, username: string},
     *     result: string,
     *     damage: int,
     *     targetHealthAfter: int,
     *     playerHealthAfter: int|null,
     *     attackerHealth: int|null,
     *     defenderHealth: int|null,
     *     isAttackerSide: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'moveNumber' => $this->moveNumber,
            'player' => $this->player,
            'target' => $this->target,
            'result' => $this->result,
            'damage' => $this->damage,
            'targetHealthAfter' => $this->targetHealthAfter,
            'playerHealthAfter' => $this->playerHealthAfter,
            'attackerHealth' => $this->attackerHealth,
            'defenderHealth' => $this->defenderHealth,
            'isAttackerSide' => $this->isAttackerSide,
        ];
    }
}
