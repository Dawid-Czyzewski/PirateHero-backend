<?php

declare(strict_types=1);

namespace App\Dto\Api\Ranking;

final readonly class PlayersRankingResponse
{
    public function __construct(
        public array $items,
        public PaginationDto $pagination,
    ) {
    }

    public function toArray(): array
    {
        return [
            'items' => array_map(static fn (PlayerRankingEntryDto $e) => $e->toArray(), $this->items),
            'pagination' => $this->pagination->toArray(),
        ];
    }
}
