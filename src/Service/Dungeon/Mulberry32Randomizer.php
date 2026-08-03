<?php

declare(strict_types=1);

namespace App\Service\Dungeon;

use App\Service\Random\RandomizerInterface;

final class Mulberry32Randomizer implements RandomizerInterface
{
    private int $state;

    public function __construct(int $seed)
    {
        $this->state = $seed;
    }

    public function int(int $min, int $max): int
    {
        if ($max < $min) {
            throw new \InvalidArgumentException('max must be >= min');
        }

        return $min + (int) floor($this->nextFloat() * ($max - $min + 1));
    }

    private function nextFloat(): float
    {
        $t = $this->state = ($this->state + 0x6D2B79F5) & 0xFFFFFFFF;
        $t = $this->imul(($t ^ (($t >> 15) & 0xFFFF)) & 0xFFFFFFFF, ($t | 1) & 0xFFFFFFFF);
        $t = ($t ^ ($t + $this->imul(($t ^ (($t >> 7) & 0x1FFFFFF)) & 0xFFFFFFFF, ($t | 61) & 0xFFFFFFFF))) & 0xFFFFFFFF;

        return (($t ^ ($t >> 14)) & 0xFFFFFFFF) / 4294967296.0;
    }

    private function imul(int $a, int $b): int
    {
        return ($a * $b) & 0xFFFFFFFF;
    }
}
