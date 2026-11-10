<?php

declare(strict_types=1);

namespace App\Identity\Domain\Model;

use App\Identity\Domain\Exception\InvalidUserData;

final readonly class Email
{
    public const MAX_LENGTH = 180;

    private string $value;

    public function __construct(string $value)
    {
        $value = mb_strtolower(trim($value));

        if (false === filter_var($value, \FILTER_VALIDATE_EMAIL)) {
            throw InvalidUserData::email('This value is not a valid email address.');
        }
        if (mb_strlen($value) > self::MAX_LENGTH) {
            throw InvalidUserData::email(\sprintf('Email must be at most %d characters long.', self::MAX_LENGTH));
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
