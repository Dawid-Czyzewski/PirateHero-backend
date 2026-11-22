<?php

declare(strict_types=1);

namespace App\Tests\Double\Combat;

use App\Combat\Domain\Random\RandomIntProvider;
use Random\Engine\Mt19937;
use Random\Randomizer;

final readonly class SeededRandomIntProvider implements RandomIntProvider
{
    private Randomizer $randomizer;

    public function __construct(int $seed)
    {
        $this->randomizer = new Randomizer(new Mt19937($seed));
    }

    public function between(int $min, int $max): int
    {
        return $this->randomizer->getInt($min, $max);
    }
}
