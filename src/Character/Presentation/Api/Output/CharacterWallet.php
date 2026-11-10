<?php

declare(strict_types=1);

namespace App\Character\Presentation\Api\Output;

final readonly class CharacterWallet
{
    public function __construct(
        public int $cebuliony,
        public int $zloteZeby,
        public int $kondycha,
        public int $zasiegi,
        public int $medale,
    ) {
    }
}
