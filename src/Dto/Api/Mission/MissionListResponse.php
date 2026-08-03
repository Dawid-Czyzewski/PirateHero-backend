<?php

declare(strict_types=1);

namespace App\Dto\Api\Mission;

final readonly class MissionListResponse
{
    /**
     * @param list<MissionDto> $missions
     */
    public function __construct(
        public array $missions,
    ) {
    }

    public function toArray(): array
    {
        return [
            'missions' => array_map(static fn (MissionDto $m) => $m->toArray(), $this->missions),
        ];
    }
}
