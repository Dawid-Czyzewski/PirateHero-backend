<?php

declare(strict_types=1);

namespace App\Dto\Api\Mission;

final readonly class MissionCancelledResponse
{
    public function __construct(
        public bool $cancelled = true,
    ) {
    }

    public function toArray(): array
    {
        return [
            'cancelled' => $this->cancelled,
        ];
    }
}
