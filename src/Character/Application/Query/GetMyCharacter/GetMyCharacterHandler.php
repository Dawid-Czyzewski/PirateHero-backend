<?php

declare(strict_types=1);

namespace App\Character\Application\Query\GetMyCharacter;

use App\Character\Domain\Exception\CharacterNotFound;
use App\Character\Domain\Repository\CharacterRepository;
use App\Shared\Domain\Model\UserId;

final readonly class GetMyCharacterHandler
{
    public function __construct(private CharacterRepository $characters)
    {
    }

    /**
     * @throws CharacterNotFound
     */
    public function __invoke(GetMyCharacterQuery $query): CharacterView
    {
        $userId = UserId::fromString($query->userId);
        $character = $this->characters->ofUserId($userId) ?? throw CharacterNotFound::forUser($userId);

        return CharacterView::fromCharacter($character);
    }
}
