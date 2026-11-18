<?php

declare(strict_types=1);

namespace App\Character\Domain\Model;

use App\Character\Domain\Exception\InvalidCharacterData;

final readonly class Experience
{
    public const MIN = 0;
    public const MIN_GAIN = 1;

    public function __construct(private int $points)
    {
        if ($points < self::MIN) {
            throw InvalidCharacterData::belowMinimum('experience', $points, self::MIN);
        }
    }

    public static function none(): self
    {
        return new self(self::MIN);
    }

    public function points(): int
    {
        return $this->points;
    }

    public function gain(int $points): self
    {
        if ($points < self::MIN_GAIN) {
            throw InvalidCharacterData::belowMinimum('experience', $points, self::MIN_GAIN);
        }

        return new self($this->points + $points);
    }

    public function reaches(int $threshold): bool
    {
        return $this->points >= $threshold;
    }

    public function spend(int $points): self
    {
        return new self($this->points - $points);
    }
}
