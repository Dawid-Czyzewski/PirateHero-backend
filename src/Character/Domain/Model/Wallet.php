<?php

declare(strict_types=1);

namespace App\Character\Domain\Model;

use App\Character\Domain\Exception\InvalidCharacterData;

final readonly class Wallet
{
    public const START_CEBULIONY = 100;
    public const START_ZLOTE_ZEBY = 10;
    public const START_KONDYCHA = 100;
    public const START_ZASIEGI = 0;
    public const START_MEDALE = 0;

    public function __construct(
        private int $cebuliony,
        private int $zloteZeby,
        private int $kondycha,
        private int $zasiegi,
        private int $medale,
    ) {
        self::assertNotNegative('cebuliony', $cebuliony);
        self::assertNotNegative('zloteZeby', $zloteZeby);
        self::assertNotNegative('kondycha', $kondycha);
        self::assertNotNegative('zasiegi', $zasiegi);
        self::assertNotNegative('medale', $medale);
    }

    public static function initial(): self
    {
        return new self(
            self::START_CEBULIONY,
            self::START_ZLOTE_ZEBY,
            self::START_KONDYCHA,
            self::START_ZASIEGI,
            self::START_MEDALE,
        );
    }

    public function cebuliony(): int
    {
        return $this->cebuliony;
    }

    public function zloteZeby(): int
    {
        return $this->zloteZeby;
    }

    public function kondycha(): int
    {
        return $this->kondycha;
    }

    public function zasiegi(): int
    {
        return $this->zasiegi;
    }

    public function medale(): int
    {
        return $this->medale;
    }

    private static function assertNotNegative(string $field, int $value): void
    {
        if ($value < 0) {
            throw InvalidCharacterData::belowMinimum($field, $value, 0);
        }
    }
}
