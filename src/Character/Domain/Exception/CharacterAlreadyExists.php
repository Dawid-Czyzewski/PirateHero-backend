<?php

declare(strict_types=1);

namespace App\Character\Domain\Exception;

use App\Shared\Domain\Model\UserId;

final class CharacterAlreadyExists extends \DomainException
{
    public static function forUser(UserId $userId): self
    {
        return new self(\sprintf('User %s already has a character.', $userId->toString()));
    }
}
