<?php

declare(strict_types=1);

namespace App\Dto\Api\Dungeon;

final readonly class DungeonOpponentDto
{
    public function __construct(
        public string $id,
        public string $name,
        public string $enemyNameKey,
        public string $avatarId,
        public int $level,
        public int $famePoints,
        public int $strength,
        public int $agility,
        public int $endurance,
        public int $intelligence,
        public int $luck,
    ) {
    }

    /**
     * @return array{
     *     id: string,
     *     name: string,
     *     enemyNameKey: string,
     *     avatarId: string,
     *     level: int,
     *     famePoints: int,
     *     strength: int,
     *     agility: int,
     *     endurance: int,
     *     intelligence: int,
     *     luck: int
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'enemyNameKey' => $this->enemyNameKey,
            'avatarId' => $this->avatarId,
            'level' => $this->level,
            'famePoints' => $this->famePoints,
            'strength' => $this->strength,
            'agility' => $this->agility,
            'endurance' => $this->endurance,
            'intelligence' => $this->intelligence,
            'luck' => $this->luck,
        ];
    }
}
