<?php

declare(strict_types=1);

namespace App\Dto\Api\Ship;

final readonly class ShipMemberSelfDto
{
    public function __construct(
        public int $id,
        public ?string $role,
        public string $joinedAt,
        public int $goldContributed,
        public int $diamondsContributed,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'role' => $this->role,
            'joinedAt' => $this->joinedAt,
            'goldContributed' => $this->goldContributed,
            'diamondsContributed' => $this->diamondsContributed,
        ];
    }
}
