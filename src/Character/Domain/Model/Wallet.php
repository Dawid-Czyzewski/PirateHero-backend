<?php

declare(strict_types=1);

namespace App\Character\Domain\Model;

use App\Character\Domain\Exception\InvalidCharacterData;

final readonly class Wallet
{
    public const START_ONIONS = 100;
    public const START_GOLD_TEETH = 10;
    public const START_REACH = 0;
    public const START_MEDALS = 0;

    public function __construct(
        private int $onions,
        private int $goldTeeth,
        private int $reach,
        private int $medals,
    ) {
        self::assertNotNegative('onions', $onions);
        self::assertNotNegative('goldTeeth', $goldTeeth);
        self::assertNotNegative('reach', $reach);
        self::assertNotNegative('medals', $medals);
    }

    public static function initial(): self
    {
        return new self(
            self::START_ONIONS,
            self::START_GOLD_TEETH,
            self::START_REACH,
            self::START_MEDALS,
        );
    }

    public function onions(): int
    {
        return $this->onions;
    }

    public function goldTeeth(): int
    {
        return $this->goldTeeth;
    }

    public function reach(): int
    {
        return $this->reach;
    }

    public function medals(): int
    {
        return $this->medals;
    }

    public function earnOnions(int $amount): self
    {
        self::assertNotNegative('onions', $amount);

        return new self($this->onions + $amount, $this->goldTeeth, $this->reach, $this->medals);
    }

    private static function assertNotNegative(string $field, int $value): void
    {
        if ($value < 0) {
            throw InvalidCharacterData::belowMinimum($field, $value, 0);
        }
    }
}
