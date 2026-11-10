<?php

declare(strict_types=1);

namespace App\Character\Domain\Model;

use App\Character\Domain\Exception\InvalidCharacterData;

final readonly class Appearance
{
    public const FACE_VARIANTS = 8;
    public const HAIR_VARIANTS = 8;
    public const BEARD_VARIANTS = 8;
    public const SKIN_VARIANTS = 5;
    public const TATTOO_VARIANTS = 8;

    public function __construct(
        private int $face,
        private int $hair,
        private int $beard,
        private int $skin,
        private int $tattoo,
    ) {
        self::assertVariant('face', $face, self::FACE_VARIANTS);
        self::assertVariant('hair', $hair, self::HAIR_VARIANTS);
        self::assertVariant('beard', $beard, self::BEARD_VARIANTS);
        self::assertVariant('skin', $skin, self::SKIN_VARIANTS);
        self::assertVariant('tattoo', $tattoo, self::TATTOO_VARIANTS);
    }

    public function face(): int
    {
        return $this->face;
    }

    public function hair(): int
    {
        return $this->hair;
    }

    public function beard(): int
    {
        return $this->beard;
    }

    public function skin(): int
    {
        return $this->skin;
    }

    public function tattoo(): int
    {
        return $this->tattoo;
    }

    private static function assertVariant(string $field, int $value, int $variants): void
    {
        if ($value < 0 || $value >= $variants) {
            throw InvalidCharacterData::outOfRange($field, $value, 0, $variants - 1);
        }
    }
}
