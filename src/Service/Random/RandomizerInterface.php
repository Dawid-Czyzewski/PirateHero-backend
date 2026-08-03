<?php

declare(strict_types=1);

namespace App\Service\Random;

interface RandomizerInterface
{
    public function int(int $min, int $max): int;
}
