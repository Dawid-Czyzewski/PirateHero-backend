<?php

declare(strict_types=1);

namespace App\Dto\Api\Fight;

final readonly class FightHistoryItemDto
{
    /**
     * @param array{id: int|string, username: string} $opponent
     */
    public function __construct(
        public int $id,
        public array $opponent,
        public string $result,
        public int $famePointsChange,
        public string $date,
        public bool $wasAttacker,
    ) {
    }

    /**
     * @return array{
     *     id: int,
     *     opponent: array{id: int|string, username: string},
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
            'opponent' => $this->opponent,
            'result' => $this->result,
            'famePointsChange' => $this->famePointsChange,
            'date' => $this->date,
            'wasAttacker' => $this->wasAttacker,
        ];
    }
}
