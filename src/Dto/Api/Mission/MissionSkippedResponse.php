<?php

declare(strict_types=1);

namespace App\Dto\Api\Mission;

final readonly class MissionSkippedResponse
{
    public function __construct(
        public int $diamondsSpent,
        public int $diamonds,
        public string $startTime,
        public bool $readyToClaim = true,
    ) {
    }

    /**
     * @return array{diamondsSpent: int, diamonds: int, startTime: string, readyToClaim: bool}
     */
    public function toArray(): array
    {
        return [
            'diamondsSpent' => $this->diamondsSpent,
            'diamonds' => $this->diamonds,
            'startTime' => $this->startTime,
            'readyToClaim' => $this->readyToClaim,
        ];
    }
}
