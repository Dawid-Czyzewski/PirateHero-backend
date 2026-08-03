<?php

declare(strict_types=1);

namespace App\Dto\Api\Work;

final readonly class WorkListResponse
{
    /**
     * @param list<WorkDto> $works
     */
    public function __construct(
        public array $works,
    ) {
    }

    public function toArray(): array
    {
        return [
            'works' => array_map(static fn (WorkDto $w) => $w->toArray(), $this->works),
        ];
    }
}
