<?php

declare(strict_types=1);

namespace App\Identity\Domain\Exception;

final class InvalidUserData extends \DomainException
{
    private function __construct(public readonly string $field, string $message)
    {
        parent::__construct($message);
    }

    public static function email(string $message): self
    {
        return new self('email', $message);
    }

    public static function nick(string $message): self
    {
        return new self('nick', $message);
    }

    public static function password(string $message): self
    {
        return new self('password', $message);
    }
}
