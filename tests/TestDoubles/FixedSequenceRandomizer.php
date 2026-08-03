<?php

declare(strict_types=1);

namespace App\Tests\TestDoubles;

use App\Service\Random\RandomizerInterface;

final class FixedSequenceRandomizer implements RandomizerInterface
{
    private int $index = 0;

    /** @param list<int> $sequence */
    public function __construct(private array $sequence)
    {
    }

    public function int(int $min, int $max): int
    {
        if (!isset($this->sequence[$this->index])) {
            return $min;
        }

        return $this->sequence[$this->index++];
    }
}
