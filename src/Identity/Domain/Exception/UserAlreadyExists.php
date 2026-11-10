<?php

declare(strict_types=1);

namespace App\Identity\Domain\Exception;

final class UserAlreadyExists extends \DomainException
{
    /**
     * @param list<'email'|'nick'> $conflicts
     */
    private function __construct(public readonly array $conflicts)
    {
        parent::__construct(
            [] === $conflicts
                ? 'User already exists.'
                : \sprintf('Already taken: %s.', implode(', ', $conflicts)),
        );
    }

    /**
     * @param list<'email'|'nick'> $conflicts
     */
    public static function withConflicts(array $conflicts): self
    {
        return new self($conflicts);
    }
}
