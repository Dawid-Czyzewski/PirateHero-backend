<?php

declare(strict_types=1);

namespace App\Dto\MiniGames;

use App\Enum\CoinFlipSide;

final readonly class CoinFlipPlayResult
{
    public function __construct(
        public bool $won,
        public CoinFlipSide $outcome,
        public int $diamondsAfter,
        public int $payoutDiamonds,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'won' => $this->won,
            'outcome' => $this->outcome->value,
            'diamondsAfter' => $this->diamondsAfter,
            'payoutDiamonds' => $this->payoutDiamonds,
        ];
    }
}
