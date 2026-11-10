<?php

declare(strict_types=1);

namespace App\Identity\Domain\Model;

use App\Identity\Domain\Exception\InvalidUserData;

final readonly class Nick
{
    public const MIN_LENGTH = 3;
    public const MAX_LENGTH = 16;

    private string $value;

    public function __construct(string $value)
    {
        $value = trim($value);
        $length = mb_strlen($value);

        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw InvalidUserData::nick(\sprintf('Nick must be between %d and %d characters long.', self::MIN_LENGTH, self::MAX_LENGTH));
        }

        $this->value = $value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
