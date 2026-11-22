<?php

declare(strict_types=1);

namespace App\Combat\Domain\Exception;

final class NpcNotFound extends \DomainException
{
    public static function withId(string $id): self
    {
        return new self(\sprintf('NPC "%s" not found.', $id));
    }
}
