<?php

declare(strict_types=1);

namespace App\Dto\Api\Training;

final readonly class TrainingStartedResponse
{
    public function __construct(
        public bool $started = true,
    ) {
    }

    public function toArray(): array
    {
        return [
            'started' => $this->started,
        ];
    }
}
