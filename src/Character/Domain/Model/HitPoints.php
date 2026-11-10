<?php

declare(strict_types=1);

namespace App\Character\Domain\Model;

final readonly class HitPoints
{
    public const PER_ENDURANCE = 10;
    public const PER_LEVEL = 5;

    private function __construct(private int $value)
    {
    }

    public static function maximum(Stats $stats, Level $level): self
    {
        return new self($stats->endurance() * self::PER_ENDURANCE + $level->value() * self::PER_LEVEL);
    }

    public function value(): int
    {
        return $this->value;
    }
}
