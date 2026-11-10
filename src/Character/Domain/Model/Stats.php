<?php

declare(strict_types=1);

namespace App\Character\Domain\Model;

use App\Character\Domain\Exception\InvalidCharacterData;

final readonly class Stats
{
    public const MIN = 1;

    public function __construct(
        private int $strength,
        private int $endurance,
        private int $technique,
        private int $charisma,
    ) {
        self::assertAtLeastMin('strength', $strength);
        self::assertAtLeastMin('endurance', $endurance);
        self::assertAtLeastMin('technique', $technique);
        self::assertAtLeastMin('charisma', $charisma);
    }

    public static function initial(): self
    {
        return new self(self::MIN, self::MIN, self::MIN, self::MIN);
    }

    public function strength(): int
    {
        return $this->strength;
    }

    public function endurance(): int
    {
        return $this->endurance;
    }

    public function technique(): int
    {
        return $this->technique;
    }

    public function charisma(): int
    {
        return $this->charisma;
    }

    private static function assertAtLeastMin(string $field, int $value): void
    {
        if ($value < self::MIN) {
            throw InvalidCharacterData::belowMinimum($field, $value, self::MIN);
        }
    }
}
