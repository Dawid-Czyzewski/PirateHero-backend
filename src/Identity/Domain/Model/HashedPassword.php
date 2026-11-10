<?php

declare(strict_types=1);

namespace App\Identity\Domain\Model;

use App\Identity\Domain\Exception\InvalidUserData;

final readonly class HashedPassword
{
    public function __construct(private string $hash)
    {
        if ('' === $hash) {
            throw InvalidUserData::password('Password hash cannot be empty.');
        }
    }

    public function toString(): string
    {
        return $this->hash;
    }
}
