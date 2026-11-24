<?php

declare(strict_types=1);

namespace App\Character\Presentation\Api\Output;

final readonly class CharacterPool
{
    public function __construct(
        public int $current,
        public int $max,
        public ?int $nextPointInSeconds,
    ) {
    }
}
