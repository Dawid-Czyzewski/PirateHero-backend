<?php

declare(strict_types=1);

namespace App\Tests\Double\Character;

use App\Character\Domain\Exception\CharacterAlreadyExists;
use App\Character\Domain\Model\Character;
use App\Character\Domain\Model\CharacterId;
use App\Character\Domain\Repository\CharacterRepository;
use App\Shared\Domain\Model\UserId;
use Symfony\Component\Uid\Uuid;

final class InMemoryCharacterRepository implements CharacterRepository
{
    /** @var array<string, Character> */
    private array $characters = [];

    public function nextIdentity(): CharacterId
    {
        return CharacterId::fromString(Uuid::v7()->toRfc4122());
    }

    public function save(Character $character): void
    {
        $existing = $this->ofUserId($character->userId());
        if (null !== $existing && !$existing->id()->equals($character->id())) {
            throw CharacterAlreadyExists::forUser($character->userId());
        }

        $this->characters[$character->id()->toString()] = $character;
    }

    public function ofId(CharacterId $id): ?Character
    {
        return $this->characters[$id->toString()] ?? null;
    }

    public function ofUserId(UserId $userId): ?Character
    {
        foreach ($this->characters as $character) {
            if ($character->userId()->equals($userId)) {
                return $character;
            }
        }

        return null;
    }

    public function count(): int
    {
        return \count($this->characters);
    }
}
