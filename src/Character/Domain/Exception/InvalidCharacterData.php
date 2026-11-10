<?php

declare(strict_types=1);

namespace App\Character\Domain\Exception;

final class InvalidCharacterData extends \DomainException
{
    private function __construct(public readonly string $field, string $message)
    {
        parent::__construct($message);
    }

    public static function belowMinimum(string $field, int $value, int $minimum): self
    {
        return new self($field, \sprintf('%s must be at least %d, %d given.', ucfirst($field), $minimum, $value));
    }

    public static function outOfRange(string $field, int $value, int $minimum, int $maximum): self
    {
        return new self($field, \sprintf('%s must be between %d and %d, %d given.', ucfirst($field), $minimum, $maximum, $value));
    }
}
