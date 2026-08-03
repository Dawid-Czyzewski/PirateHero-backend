<?php

declare(strict_types=1);

namespace App\Dto\Api\Training;

final readonly class TrainingCancelledResponse
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
