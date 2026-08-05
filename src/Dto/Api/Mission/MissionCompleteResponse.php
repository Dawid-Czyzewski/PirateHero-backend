<?php

declare(strict_types=1);

namespace App\Dto\Api\Mission;

final readonly class MissionCompleteResponse
{
    /**
     * @param list<MissionDto> $missions
     * @param array<string, mixed>|null $newLevel
     */
    public function __construct(
        public array $missions,
        public int $earnedGold,
        public int $earnedExp,
        public int $bonusPercent,
        public ?array $newLevel = null,
        public int $diamondsSpent = 0,
    ) {
    }

    public function toArray(): array
    {
        $payload = [
            'missions' => array_map(static fn (MissionDto $m) => $m->toArray(), $this->missions),
            'earnedGold' => $this->earnedGold,
            'earnedExp' => $this->earnedExp,
            'bonusPercent' => $this->bonusPercent,
            'diamondsSpent' => $this->diamondsSpent,
        ];

        if ($this->newLevel !== null) {
            $payload['newLevel'] = $this->newLevel;
        }

        return $payload;
    }
}
