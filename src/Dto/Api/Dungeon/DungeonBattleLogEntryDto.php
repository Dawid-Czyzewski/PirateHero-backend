<?php

declare(strict_types=1);

namespace App\Dto\Api\Dungeon;

final readonly class DungeonBattleLogEntryDto
{
    public function __construct(
        public bool $attackerIsPlayer,
        public int $damage,
        public bool $critical,
    ) {
    }

    /**
     * @return array{attackerIsPlayer: bool, damage: int, critical: bool}
     */
    public function toArray(): array
    {
        return [
            'attackerIsPlayer' => $this->attackerIsPlayer,
            'damage' => $this->damage,
            'critical' => $this->critical,
        ];
    }
}
