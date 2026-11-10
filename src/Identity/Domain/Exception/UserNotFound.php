<?php

declare(strict_types=1);

namespace App\Identity\Domain\Exception;

use App\Shared\Domain\Model\UserId;

final class UserNotFound extends \DomainException
{
    public static function withId(UserId $id): self
    {
        return new self(\sprintf('User %s not found.', $id->toString()));
    }
}
