<?php

declare(strict_types=1);

namespace App\Dto\Api\Ranking;

final readonly class PaginationDto
{
    public function __construct(
        public int $page,
        public int $limit,
        public int $total,
        public int $totalPages,
    ) {
    }

    public function toArray(): array
    {
        return [
            'page' => $this->page,
            'limit' => $this->limit,
            'total' => $this->total,
            'totalPages' => $this->totalPages,
        ];
    }
}
