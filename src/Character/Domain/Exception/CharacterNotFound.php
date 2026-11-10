<?php

declare(strict_types=1);

namespace App\Character\Domain\Exception;

use App\Shared\Domain\Model\UserId;

final class CharacterNotFound extends \DomainException
{
    public static function forUser(UserId $userId): self
    {
        return new self(\sprintf('User %s has no character.', $userId->toString()));
    }
}
