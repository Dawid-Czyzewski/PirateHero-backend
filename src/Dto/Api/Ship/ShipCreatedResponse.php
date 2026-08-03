<?php

declare(strict_types=1);

namespace App\Dto\Api\Ship;

final readonly class ShipCreatedResponse
{
    /**
     * @param array{
     *     id: int,
     *     title: string,
     *     description: string|null,
     *     internalNotes: string|null,
     *     createdAt: string,
     *     maxMembers: int,
     *     hullUpgrade: int
     * } $ship
     */
    public function __construct(
        public array $ship,
    ) {
    }

    public function toArray(): array
    {
        return [
            'ship' => $this->ship,
        ];
    }
}
