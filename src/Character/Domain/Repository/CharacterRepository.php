<?php

declare(strict_types=1);

namespace App\Character\Domain\Repository;

use App\Character\Domain\Exception\CharacterAlreadyExists;
use App\Character\Domain\Model\Character;
use App\Character\Domain\Model\CharacterId;
use App\Shared\Domain\Model\UserId;

interface CharacterRepository
{
    public function nextIdentity(): CharacterId;

    /**
     * @throws CharacterAlreadyExists
     */
    public function save(Character $character): void;

    public function ofId(CharacterId $id): ?Character;

    public function ofUserId(UserId $userId): ?Character;
}
