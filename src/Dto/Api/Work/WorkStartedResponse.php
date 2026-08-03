<?php

declare(strict_types=1);

namespace App\Dto\Api\Work;

final readonly class WorkStartedResponse
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
