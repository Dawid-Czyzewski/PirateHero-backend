<?php

declare(strict_types=1);

namespace App\Combat\Domain\Random;

interface RandomIntProvider
{
    public function between(int $min, int $max): int;
}
