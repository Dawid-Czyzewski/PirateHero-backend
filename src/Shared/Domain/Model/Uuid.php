<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model;

use App\Shared\Domain\Exception\InvalidIdentifier;

abstract readonly class Uuid
{
    private const PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/';

    final private function __construct(private string $value)
    {
    }

    public static function fromString(string $value): static
    {
        $value = strtolower($value);
        if (1 !== preg_match(self::PATTERN, $value)) {
            throw InvalidIdentifier::notAUuid($value);
        }

        return new static($value);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return static::class === $other::class && $this->value === $other->value;
    }
}
