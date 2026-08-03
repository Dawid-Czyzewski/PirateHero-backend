<?php

declare(strict_types=1);

namespace App\Service\Random;

final class NativeRandomizer implements RandomizerInterface
{
    public function int(int $min, int $max): int
    {
        return random_int($min, $max);
    }
}
