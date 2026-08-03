<?php

declare(strict_types=1);

namespace App\Dto\Api\Ranking;

final readonly class ShipRankingEntryDto
{
    public function __construct(
        public string $id,
        public string $title,
        public int $totalFamePoints,
        public int $memberCount,
        public array $memberIds,
        public bool $requiresInvitation,
        public int $maxMembers,
        public ?string $captainUsername,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'totalFamePoints' => $this->totalFamePoints,
            'memberCount' => $this->memberCount,
            'memberIds' => $this->memberIds,
            'requiresInvitation' => $this->requiresInvitation,
            'maxMembers' => $this->maxMembers,
            'captainUsername' => $this->captainUsername,
        ];
    }
}
