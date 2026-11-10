<?php

declare(strict_types=1);

namespace App\Character\Domain\Model;

use App\Character\Domain\Exception\InvalidCharacterData;

final readonly class Level
{
    public const MIN = 1;

    public function __construct(private int $value)
    {
        if ($value < self::MIN) {
            throw InvalidCharacterData::belowMinimum('level', $value, self::MIN);
        }
    }

    public static function start(): self
    {
        return new self(self::MIN);
    }

    public function value(): int
    {
        return $this->value;
    }
}
