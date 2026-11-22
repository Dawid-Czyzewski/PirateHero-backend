<?php

declare(strict_types=1);

namespace App\Combat\Infrastructure\Random;

use App\Combat\Domain\Random\RandomIntProvider;

final readonly class PhpRandomIntProvider implements RandomIntProvider
{
    public function between(int $min, int $max): int
    {
        return random_int($min, $max);
    }
}
