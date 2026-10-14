<?php

declare(strict_types=1);

namespace App\Dto\MiniGames;

final readonly class TavernDicePlayResult
{
    public function __construct(
        public bool $won,
        public bool $tied,
        public int $playerRoll,
        public int $dealerRoll,
        public int $goldAfter,
        public int $payoutGold,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'won' => $this->won,
            'tied' => $this->tied,
            'playerRoll' => $this->playerRoll,
            'dealerRoll' => $this->dealerRoll,
            'goldAfter' => $this->goldAfter,
            'payoutGold' => $this->payoutGold,
        ];
    }
}
