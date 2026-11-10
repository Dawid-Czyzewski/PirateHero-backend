<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Persistence\Doctrine\Type;

use App\Shared\Domain\Model\UserId;

final class UserIdType extends UuidType
{
    public const NAME = 'user_id';

    protected function idClass(): string
    {
        return UserId::class;
    }
}
