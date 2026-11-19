<?php

declare(strict_types=1);

namespace App\Character\Application\Command\CreateCharacter;

use App\Character\Domain\Exception\CharacterAlreadyExists;
use App\Character\Domain\Exception\InvalidCharacterData;
use App\Character\Domain\Model\Appearance;
use App\Character\Domain\Model\Character;
use App\Character\Domain\Model\CharacterId;
use App\Character\Domain\Repository\CharacterRepository;
use App\Shared\Domain\Model\UserId;
use Psr\Clock\ClockInterface;

final readonly class CreateCharacterHandler
{
    public function __construct(
        private CharacterRepository $characters,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @throws InvalidCharacterData
     * @throws CharacterAlreadyExists
     */
    public function __invoke(CreateCharacterCommand $command): CharacterId
    {
        $userId = UserId::fromString($command->userId);
        $appearance = new Appearance($command->face, $command->hair, $command->beard, $command->skin, $command->tattoo);

        if (null !== $this->characters->ofUserId($userId)) {
            throw CharacterAlreadyExists::forUser($userId);
        }

        $character = Character::create($this->characters->nextIdentity(), $userId, $appearance, $this->clock->now());
        $this->characters->save($character);

        return $character->id();
    }
}
