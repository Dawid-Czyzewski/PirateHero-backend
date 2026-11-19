<?php

declare(strict_types=1);

namespace App\Character\Presentation\Api\Output;

final readonly class CharacterWallet
{
    public function __construct(
        public int $onions,
        public int $goldTeeth,
        public int $reach,
        public int $medals,
    ) {
    }
}
