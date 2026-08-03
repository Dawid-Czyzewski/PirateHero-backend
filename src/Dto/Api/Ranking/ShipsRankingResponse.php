<?php

declare(strict_types=1);

namespace App\Dto\Api\Ranking;

final readonly class ShipsRankingResponse
{
    public function __construct(
        public array $items,
        public PaginationDto $pagination,
    ) {
    }

    public function toArray(): array
    {
        return [
            'items' => array_map(static fn (ShipRankingEntryDto $e) => $e->toArray(), $this->items),
            'pagination' => $this->pagination->toArray(),
        ];
    }
}
