<?php

declare(strict_types=1);

namespace App\Dto\Api\Ship;

final readonly class ShipPreviewResponse
{
    /**
     * @param list<ShipMemberDto> $members
     */
    public function __construct(
        public int $id,
        public string $title,
        public ?string $description,
        public string $createdAt,
        public int $skillsUpgrade,
        public int $workUpgrade,
        public int $missionsUpgrade,
        public int $hullUpgrade,
        public int $maxMembers,
        public bool $requiresInvitation,
        public int $famePoints,
        public array $members,
        public int $membersCount,
        public bool $hasPendingRequest,
        public bool $isOwner,
        public bool $isFull,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'createdAt' => $this->createdAt,
            'skillsUpgrade' => $this->skillsUpgrade,
            'workUpgrade' => $this->workUpgrade,
            'missionsUpgrade' => $this->missionsUpgrade,
            'hullUpgrade' => $this->hullUpgrade,
            'maxMembers' => $this->maxMembers,
            'requiresInvitation' => $this->requiresInvitation,
            'famePoints' => $this->famePoints,
            'members' => array_map(static fn (ShipMemberDto $m) => $m->toArray(), $this->members),
            'membersCount' => $this->membersCount,
            'hasPendingRequest' => $this->hasPendingRequest,
            'isOwner' => $this->isOwner,
            'isFull' => $this->isFull,
        ];
    }
}
