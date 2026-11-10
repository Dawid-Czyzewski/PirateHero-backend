<?php

declare(strict_types=1);

namespace App\Character\Infrastructure\Persistence\Doctrine\Type;

use App\Character\Domain\Model\CharacterId;
use App\Shared\Infrastructure\Persistence\Doctrine\Type\UuidType;

final class CharacterIdType extends UuidType
{
    public const NAME = 'character_id';

    protected function idClass(): string
    {
        return CharacterId::class;
    }
}
