<?php

declare(strict_types=1);

namespace App\Combat\Domain\Exception;

final class InvalidFighter extends \DomainException
{
    public static function belowMinimum(string $field, int $value, int $minimum): self
    {
        return new self(\sprintf('%s must be at least %d, %d given.', ucfirst($field), $minimum, $value));
    }

    public static function blankName(): self
    {
        return new self('Fighter name must not be blank.');
    }
}
