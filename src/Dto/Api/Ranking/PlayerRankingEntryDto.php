<?php

declare(strict_types=1);

namespace App\Dto\Api\Ranking;

final readonly class PlayerRankingEntryDto
{
    public function __construct(
        public string $id,
        public string $username,
        public int $famePoints,
        public int $experiencePoints,
        public ?array $level,
        public ?array $ship,
        public ?array $equippedTitle = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'famePoints' => $this->famePoints,
            'experiencePoints' => $this->experiencePoints,
            'level' => $this->level,
            'ship' => $this->ship,
            'equippedTitle' => $this->equippedTitle,
        ];
    }
}
