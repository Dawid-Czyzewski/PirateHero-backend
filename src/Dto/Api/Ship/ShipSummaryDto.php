<?php

declare(strict_types=1);

namespace App\Dto\Api\Ship;

final readonly class ShipSummaryDto
{
    public function __construct(
        public int $id,
        public string $title,
        public ?string $description,
        public ?string $internalNotes,
        public string $createdAt,
        public int $gold,
        public int $diamonds,
        public int $skillsUpgrade,
        public int $workUpgrade,
        public int $missionsUpgrade,
        public int $hullUpgrade,
        public int $maxMembers,
        public bool $requiresInvitation,
        public int $famePoints,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'internalNotes' => $this->internalNotes,
            'createdAt' => $this->createdAt,
            'gold' => $this->gold,
            'diamonds' => $this->diamonds,
            'skillsUpgrade' => $this->skillsUpgrade,
            'workUpgrade' => $this->workUpgrade,
            'missionsUpgrade' => $this->missionsUpgrade,
            'hullUpgrade' => $this->hullUpgrade,
            'maxMembers' => $this->maxMembers,
            'requiresInvitation' => $this->requiresInvitation,
            'famePoints' => $this->famePoints,
        ];
    }
}
