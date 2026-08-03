<?php

declare(strict_types=1);

namespace App\Dto\Api\Fight;

final readonly class ShipsFightHistoryItemDto
{
    /**
     * @param array{id: int|string, title: string} $yourShip
     * @param array{id: int|string, title: string} $opponentShip
     */
    public function __construct(
        public int $id,
        public array $yourShip,
        public array $opponentShip,
        public string $result,
        public int $famePointsChange,
        public string $date,
        public bool $wasAttacker,
    ) {
    }

    /**
     * @return array{
     *     id: int,
     *     yourShip: array{id: int|string, title: string},
     *     opponentShip: array{id: int|string, title: string},
     *     result: string,
     *     famePointsChange: int,
     *     date: string,
     *     wasAttacker: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'yourShip' => $this->yourShip,
            'opponentShip' => $this->opponentShip,
            'result' => $this->result,
            'famePointsChange' => $this->famePointsChange,
            'date' => $this->date,
            'wasAttacker' => $this->wasAttacker,
        ];
    }
}
