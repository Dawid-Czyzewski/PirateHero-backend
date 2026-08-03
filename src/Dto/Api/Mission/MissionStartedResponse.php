<?php

declare(strict_types=1);

namespace App\Dto\Api\Mission;

final readonly class MissionStartedResponse
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
