<?php

declare(strict_types=1);

namespace App\Character\Presentation\Api\Output;

final readonly class CharacterStats
{
    public function __construct(
        public int $strength,
        public int $endurance,
        public int $technique,
        public int $charisma,
    ) {
    }
}
