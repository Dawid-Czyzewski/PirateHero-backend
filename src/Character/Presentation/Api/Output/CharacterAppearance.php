<?php

declare(strict_types=1);

namespace App\Character\Presentation\Api\Output;

final readonly class CharacterAppearance
{
    public function __construct(
        public int $face,
        public int $hair,
        public int $beard,
        public int $skin,
        public int $tattoo,
    ) {
    }
}
