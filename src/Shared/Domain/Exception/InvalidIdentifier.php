<?php

declare(strict_types=1);

namespace App\Shared\Domain\Exception;

final class InvalidIdentifier extends \DomainException
{
    public static function notAUuid(string $value): self
    {
        return new self(\sprintf('"%s" is not a valid UUID.', $value));
    }
}
