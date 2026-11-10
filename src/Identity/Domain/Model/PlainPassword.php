<?php

declare(strict_types=1);

namespace App\Identity\Domain\Model;

use App\Identity\Domain\Exception\InvalidUserData;

final readonly class PlainPassword
{
    public const MIN_LENGTH = 8;
    public const MAX_LENGTH = 4096;

    private string $value;

    public function __construct(#[\SensitiveParameter] string $value)
    {
        $length = mb_strlen($value);

        if ($length < self::MIN_LENGTH) {
            throw InvalidUserData::password(\sprintf('Password must be at least %d characters long.', self::MIN_LENGTH));
        }
        if ($length > self::MAX_LENGTH) {
            throw InvalidUserData::password('Password is too long.');
        }

        $this->value = $value;
    }

    public function reveal(): string
    {
        return $this->value;
    }
}
