<?php

declare(strict_types=1);

namespace App\Character\Application\Command\CreateCharacter;

final readonly class CreateCharacterCommand
{
    public function __construct(
        public string $userId,
        public int $face,
        public int $hair,
        public int $beard,
        public int $skin,
        public int $tattoo,
    ) {
    }
}
