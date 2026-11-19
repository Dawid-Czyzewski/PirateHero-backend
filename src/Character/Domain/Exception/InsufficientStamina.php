<?php

declare(strict_types=1);

namespace App\Character\Domain\Exception;

final class InsufficientStamina extends \DomainException
{
    private function __construct(public readonly int $cost, public readonly int $available, string $message)
    {
        parent::__construct($message);
    }

    public static function forCost(int $cost, int $available): self
    {
        return new self($cost, $available, \sprintf('Not enough stamina: %d needed, %d available.', $cost, $available));
    }
}
