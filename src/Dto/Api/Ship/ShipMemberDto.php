<?php

declare(strict_types=1);

namespace App\Dto\Api\Ship;

final readonly class ShipMemberDto
{
    /**
     * @param array{
     *     id: int|string,
     *     username: string,
     *     avatarName: string|null,
     *     level: string,
     *     levelId: int,
     *     famePoints?: int
     * } $user
     */
    public function __construct(
        public int $id,
        public ?string $role,
        public string $joinedAt,
        public int $goldContributed,
        public int $diamondsContributed,
        public array $user,
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
            'user' => $this->user,
        ];
    }
}
